<?php

 

namespace TIG\OpenFreight\Setup;



use Magento\Eav\Setup\EavSetup;

use Magento\Eav\Setup\EavSetupFactory;

use Magento\Framework\Setup\ModuleContextInterface;

use Magento\Framework\Setup\ModuleDataSetupInterface;

use Magento\Framework\Setup\UpgradeDataInterface;

 

class UpgradeData implements UpgradeDataInterface {



    private $eavSetupFactory;

    

    public function __construct(EavSetupFactory $eavSetupFactory)

    {

        $this->eavSetupFactory = $eavSetupFactory;

    }

 

    public function upgrade( ModuleDataSetupInterface $setup, ModuleContextInterface $context ) {

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

 

        $setup->startSetup();

        

        if(version_compare($context->getVersion(), '2.22.4') < 0) {

            $eavSetup->addAttribute(

                \Magento\Catalog\Model\Product::ENTITY,

                'tig_free_shipping',

                [

                    'type' => 'int',

                    'backend' => '',

                    'frontend' => '',

                    'label' => 'Exclude from Quoting',

                    'input' => 'select',

                    'class' => '',

                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,

                    'group' => 'TIG',

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

                'tig_package_qty',

                [

                    'type' => 'int',

                    'backend' => '',

                    'frontend' => '',

                    'label' => 'Package Qty',

                    'class' => '',

                    'source' => '',

                    'group' => 'TIG',

                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,

                    'visible' => true,

                    'required' => true,

                    'user_defined' => false,

                    'default' => 1,

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

                'tig_weight',

                [

                    'type' => 'decimal',

                    'backend' => '',

                    'frontend' => '',

                    'label' => 'Package Weight',

                    'class' => '',

                    'source' => '',

                    'group' => 'TIG',

                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,

                    'visible' => true,

                    'required' => true,

                    'user_defined' => false,

                    'default' => 1,

                    'searchable' => false,

                    'filterable' => false,

                    'comparable' => false,

                    'visible_on_front' => false,

                    'used_in_product_listing' => true,

                    'unique' => false,

                    'apply_to' => ''

                ]

            );



            // Adds the Postcode/City Lookup Table

            $connection = $setup->getConnection();

            $tableName = $setup->getTable('tig_pcs');

            $file =  file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pcs.csv');

            $csv = array_map('str_getcsv', $file);

            $data = $csv;

            $first = true;

            $sql = "";

            $count = 1;

            foreach ($data as $value) {



                if ($first) {

                    $first = false;

                    continue;

                }



                // Do in increments of 1000 for insert query

                if ($count > 1000) {

                    $sql = substr($sql, 0, -1);

                    $query = "INSERT INTO " . $tableName . "

                    (postcode, city) VALUES " . $sql;

                    $connection->query($query);

                    $query = "";

                    $sql = "";

                    $count = 0;

                }

            

                $postcode = $value[0];

                // Escape special character "'" in city name

                $city = str_replace("'","\'",$value[1]);

                // strip out all whitespace

                $city = preg_replace('/\s*/', '', $city);

                // convert the string to all lowercase

                $city = strtolower($city);

            

                $sql .= "('$postcode','$city'),";



                $count ++;

            }

            $sql = substr($sql, 0, -1);



            $query = "INSERT INTO " . $tableName . "

            (postcode, city) VALUES " . $sql;

            $connection->query($query);



        }

        $setup->endSetup();

    }

}