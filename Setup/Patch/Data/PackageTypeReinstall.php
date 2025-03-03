<?php

namespace TIG\OpenFreight\Setup\Patch\Data;

use TIG\OpenFreight\Model\Source\PackageType as PackageTypeSource;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class PackageTypeReinstall implements DataPatchInterface, PatchRevertableInterface
{
    const PACKAGE_TYPE_ATTRIBUTE = 'tig_package_type';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(
            Product::ENTITY,
            self::PACKAGE_TYPE_ATTRIBUTE
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            self::PACKAGE_TYPE_ATTRIBUTE,
        [
            'type' => 'varchar',
            'backend' => '',
            'frontend' => '',
            'label' => 'Package Type',
            'input' => 'select',
            'class' => '',
            'source' => PackageTypeSource::class,
            'group' => 'TIG',
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible' => true,
            'required' => true,
            'user_defined' => false,
            'default' => 'Carton',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => ''
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, self::PACKAGE_TYPE_ATTRIBUTE);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
