<?php

namespace NeoSolax\DataImport\Console\Command;

use Exception;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Sales\Model\Order;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOrderGrid extends Command
{

    /**
     * @var Order
     */
    private Order $order;
    /**
     * @var State
     */
    private State $state;

    public function __construct(
        Order $order,
        State $state,
        string $name = null
    ) {
        $this->order = $order;
        $this->state = $state;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('import:order:grid');
        $this->setDescription('Import Sales Order Attributes from old Database to New Database');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = fopen('csv/orders_grid.csv', 'r', '"'); // set path to the CSV file

        if ($file !== false) {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);

            // add logging capability
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/import-new.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $header = fgetcsv($file); // get data headers and skip 1st row

            while ($row = fgetcsv($file, 3000, ",")) {
                $data_count = count($row);
                if ($data_count < 1) {
                    continue;
                }

                $data = [];
                $data = array_combine($header, $row);
                $collection = $this->order->loadByIncrementId($data['ID']);
                if ($collection->getId() && $collection->getBillingAddress()->getTelephone()) {
                    try {
                        $collection->setPhoneNumber($collection->getBillingAddress()->getTelephone());
                        $collection->save();

                        echo 'Order update ' . $data['ID'] . ' imported successfully' . PHP_EOL;
                    } catch (Exception $e) {
                        $logger->info('Error importing Order: ' . $data['ID'] . '. ' . $e->getMessage());
                        echo "Not add Order : " . $data['ID'] . '  ' . $e->getMessage() . "\n";
                        continue;
                    }
                }
            }
        }

        fclose($file);
    }
}
