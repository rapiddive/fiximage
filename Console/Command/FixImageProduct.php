<?php
/**
 * ImagesResizeCommand
 *
 * @copyright Copyright © 2019 RapidDive Tech. All rights reserved.
 * @author    Rapiddive Tech <rapiddive1@gmail.com>
 */

namespace Rapidive\FixImage\Console\Command;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\ObjectManagerInterface;
use Magento\MediaStorage\Service\ImageResize;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixImageProduct extends Command
{
    const PRODUCT_KEY = 'products';

    private $progressBarFactory;
    /**
     * @var State
     */
    private $appState;
    /**
     * @var ImageResize
     */
    private $resize;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Collection
     */
    private $productCollection;

    /**
     * @param State $appState
     * @param ImageResize $resize
     * @param ObjectManagerInterface $objectManager
     * @param ProductRepositoryInterface $productRepository
     * @param Collection $productCollection
     * @param ProgressBarFactory $progressBarFactory
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        State $appState,
        ImageResize $resize,
        ObjectManagerInterface $objectManager,
        ProductRepositoryInterface $productRepository,
        Collection $productCollection,
        ProgressBarFactory $progressBarFactory = null
    ) {
        parent::__construct();
        $this->appState = $appState;
        $this->progressBarFactory = $progressBarFactory
            ?: ObjectManager::getInstance()->get(ProgressBarFactory::class);
        $this->resize = $resize;
        $this->objectManager = $objectManager;
        $this->productRepository = $productRepository;
        $this->productCollection = $productCollection;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::PRODUCT_KEY,
                '',
                InputOption::VALUE_OPTIONAL,
                __('comma separated product sku')
            )
        ];
        $this->setName('firebear:image:resize')
            ->setDescription('Resize of product Images')
            ->setDefinition($options);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(Area::AREA_GLOBAL);
            $productsArray = array_filter(explode(',', $input->getOption(self::PRODUCT_KEY)));

            if (empty($productsArray)) {
                foreach ($this->productCollection->toArray() as $p) {
                    $productsArray[] = $p['sku'];
                }
            }

            /** @var ProgressBar $progress */
            $progress = $this->progressBarFactory->create(
                [
                    'output' => $output,
                    'max' => count($productsArray)
                ]
            );

            $progress->setFormat(
                "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
            );

            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }

            foreach ($productsArray as $productSku) {
                $product = $this->productRepository->get($productSku);
                $progress->setMaxSteps(count($product->getMediaGalleryEntries()));
                foreach ($product->getMediaGalleryEntries() as $mediaGalleryEntry) {
                    $this->resize->resizeFromImageName($mediaGalleryEntry->getFile());
                    $progress->setMessage($mediaGalleryEntry->getFile());
                    $progress->advance();
                }
            }
            return Cli::RETURN_SUCCESS;
        } catch (Exception $e) {
            var_dump($productsArray);
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return Cli::RETURN_FAILURE;
        }
        $output->write(PHP_EOL);
        return Cli::RETURN_SUCCESS;
    }
}
