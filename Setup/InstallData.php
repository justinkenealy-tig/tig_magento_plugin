<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace TIG\OpenFreight\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{

    private $eavSetupFactory;
    
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }
    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);


        // Add new Attribute group
        $groupName = 'TIG';
        $entityTypeId = $eavSetup->getEntityTypeId('catalog_product');
        $attributeSetId = $eavSetup->getDefaultAttributeSetId($entityTypeId);
        $eavSetup->addAttributeGroup($entityTypeId, $attributeSetId, $groupName, 100);
        //$attributeGroupId = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, $groupName);
        
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'tig_dg',
			[
				'type' => 'int',
				'backend' => '',
				'frontend' => '',
				'label' => 'DG',
				'input' => 'select',
				'class' => '',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'group' => $groupName,
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => true,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => ''
			]
        );
        
        $eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'tig_length',
			[
				'type' => 'decimal',
				'backend' => '',
				'frontend' => '',
				'label' => 'Length',
				'class' => '',
                'source' => '',
                'group' => $groupName,
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => true,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => ''
			]
        );
        $eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'tig_width',
			[
				'type' => 'decimal',
				'backend' => '',
				'frontend' => '',
				'label' => 'Width',
				'class' => '',
                'source' => '',
                'group' => $groupName,
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => true,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => ''
			]
        );
        $eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'tig_height',
			[
				'type' => 'decimal',
				'backend' => '',
				'frontend' => '',
				'label' => 'Height',
				'class' => '',
                'source' => '',
                'group' => $groupName,
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => true,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => ''
			]
        );
				$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'tig_volume',
			[
				'type' => 'decimal',
				'backend' => '',
				'frontend' => '',
				'label' => 'Volume',
				'class' => '',
								'source' => '',
								'group' => $groupName,
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => true,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => ''
			]
				);
	}
}