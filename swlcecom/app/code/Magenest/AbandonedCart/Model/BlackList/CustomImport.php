<?php

namespace Magenest\AbandonedCart\Model\BlackList;

use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magenest\AbandonedCart\Model\BlackList\RowValidatorInterface as ValidatorInterface;


class CustomImport extends \Magento\ImportExport\Model\Import\AbstractEntity
{
    const ID           = 'id';
    const ADDRESS      = 'address';
    const TABLE_Entity = 'magenest_abacar_blacklist';

    /**
     * Validation failure message template definitions
     * @var array
     */
    protected $_messageTemplates    = [
        ValidatorInterface::ERROR_MESSAGE_IS_EMPTY => 'Message is empty',
    ];

    protected $_permanentAttributes = [self::ID];

    /**
     * If we should check column names
     * @var bool
     */
    protected $needColumnCheck = true;

    /**
     * Valid column names
     * @array
     */
    protected $validColumnNames = [
        self::ID,
        self::ADDRESS
    ];

    /**
     * Need to log in import history
     * @var bool
     */
    protected $logInHistory = true;

    protected $_validators  = [];

    protected $_connection;

    protected $_resource;

    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        $this->jsonHelper        = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper   = $resourceHelper;
        $this->_dataSourceModel  = $importData;
        $this->_resource         = $resource;
        $this->_connection       = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator   = $errorAggregator;
    }

    public function getValidColumnNames()
    {
        return $this->validColumnNames;
    }

    public function getEntityTypeCode()
    {
        return 'abandonedcart_blacklist';
    }

    public function validateRow(array $rowData, $rowNum)
    {
        $title = false;
        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    protected function _importData()
    {
        $this->saveEntity();
        return true;
    }

    public function saveEntity()
    {
        $this->saveAndReplaceEntity();
        return $this;
    }

    public function saveAndReplaceEntity()
    {
        $behavior  = $this->getBehavior();
        $listTitle = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityList = [];
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addRowError(ValidatorInterface::ERROR_MESSAGE_IS_EMPTY, $rowNum);
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                $rowTtile                = $rowData[self::ID];
                $listTitle[]             = $rowTtile;
                $entityList[$rowTtile][] = [
                    self::ID      => $rowData[self::ID],
                    self::ADDRESS => $rowData[self::ADDRESS]
                ];
            }
            if (\Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE == $behavior) {
                if ($listTitle) {
                    if ($this->deleteEntityFinish(array_unique($listTitle), $this->_connection->getTableName(self::TABLE_Entity))) {
                        $this->saveEntityFinish($entityList, $this->_connection->getTableName(self::TABLE_Entity));
                    }
                }
            } elseif (\Magento\ImportExport\Model\Import::BEHAVIOR_APPEND == $behavior) {
                $this->saveEntityFinish($entityList, $this->_connection->getTableName(self::TABLE_Entity));
            }
        }
        return $this;
    }

    protected function saveEntityFinish(array $entityData, $table)
    {
        if ($entityData) {
            $tableName = $this->_connection->getTableName($table);
            $entityIn  = [];
            foreach ($entityData as $id => $entityRows) {
                foreach ($entityRows as $row) {
                    $entityIn[] = $row;
                }
            }
            if ($entityIn) {
                $this->_connection->insertOnDuplicate($tableName, $entityIn, [
                    self::ID,
                    self::ADDRESS,
                ]);
            }
        }
        return $this;
    }
}