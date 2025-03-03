<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace TIG\OpenFreight\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '2.0.1') < 0) {

            $table = $installer->getConnection()->newTable(
                $installer->getTable('tig_pcs')
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            )->addColumn(
                'postcode',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                ['nullable' => false],
                'Postcode'
            )->addColumn(
                'city',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                64,
                ['nullable' => false],
                'City'
            )->addIndex(
                $installer->getIdxName('tig_pcs', ['postcode', 'city']),
                ['postcode', 'city']
            );
            $installer->getConnection()->createTable($table);
        }

        $setup->endSetup();
    }

}
