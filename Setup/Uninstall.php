<?php

namespace TIG\OpenFreight\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{

    protected $eavSetupFactory;
    
    public function __construct(\Magento\Eav\Setup\EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create();

        // EAV Attributes to be uninstalled
        $filter = [
            'tig_dg',
            'tig_package_qty',
            'tig_length',
            'tig_width',
            'tig_height',
            'tig_volume'
        ];

        foreach ($filter as $value ) {
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $filter);
        }

        // Remove TIG Product Attribute Group
        $entityTypeId = $eavSetup->getEntityTypeId('catalog_product');
        $attributeSetId = $eavSetup->getDefaultAttributeSetId($entityTypeId);

        $eavSetup->removeAttributeGroup($entityTypeId, $attributeSetId, 'TIG');
        

        $setup->endSetup();
    }
}