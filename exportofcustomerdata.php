<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class ExportOfCustomerData extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'exportofcustomerdata';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Michał Drożdżyński';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Export of customer data');
        $this->description = $this->l('Export of customer data');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('exportdata')) == true) {
            $this->postProcess();
        }

        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $query = 'SELECT '. _DB_PREFIX_ . 'customer.id_customer, '. _DB_PREFIX_ . 'customer.firstname, '. _DB_PREFIX_ . 'customer.lastname,'. _DB_PREFIX_ . 'customer.email, phone FROM '. _DB_PREFIX_ . 'customer INNER JOIN ps_address ON '. _DB_PREFIX_ . 'customer.id_customer = '. _DB_PREFIX_ . 'address.id_customer GROUP BY '. _DB_PREFIX_ . 'customer.id_customer ';
        $result = Db::getInstance()->executeS($query);

        $fields_list = array(
          'id_customer'=> array(
              'title' => "ID",
              'align' => 'center',
              'class' => 'fixed-width-xs',
              'search' => false,
            ),
          'firstname' => array(
              'title' => 'Firstname',
              'orderby' => true,
              'class' => 'fixed-width-xxl',
              'search' => false,
            ),
            'lastname' => array(
                'title' => 'Lastname',
                'orderby' => true,
                'class' => 'fixed-width-xxl',
                'search' => false,
              ),
              'email' => array(
                'title' => 'Email',
                'orderby' => true,
                'class' => 'fixed-width-xxl',
                'search' => false,
              ),
              'lastname' => array(
                'title' => 'Lastname',
                'orderby' => true,
                'class' => 'fixed-width-xxl',
                'search' => false,
              ),
              'phone' => array(
                'title' => 'phone',
                'orderby' => true,
                'class' => 'fixed-width-xxl',
                'search' => false,
              ),
            
        );
  
        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->identifier = 'id_customer';
        $helper->table = 'customer';
        $helper->show_toolbar = true;
        $helper->no_link = true; 
        $helper->_default_pagination = 10;
        $helper->_pagination = array(10, 50, 100);
        $helper->toolbar_btn['export'] = [
            'href' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name, 'module_name' => $this->name, 'exportdata' => '']),
            'desc' => $this->l('Export data'),
        ];
        $helper->module = $this;
        $helper->title = $this->l('Customer Addresses');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->listTotal = count($result);
        $page = ( $page = Tools::getValue( 'submitFilter' . $helper->table ) ) ? $page : 1;
        $pagination = ( $pagination = Tools::getValue( $helper->table . '_pagination' ) ) ? $pagination : 10;
        $content = $this->paginate_content( $result, $page, $pagination );
        return $helper->generateList($content, $fields_list);  
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $query = 'SELECT '. _DB_PREFIX_ . 'customer.id_customer, '. _DB_PREFIX_ . 'customer.firstname, '. _DB_PREFIX_ . 'customer.lastname,'. _DB_PREFIX_ . 'customer.email, phone FROM '. _DB_PREFIX_ . 'customer INNER JOIN ps_address ON '. _DB_PREFIX_ . 'customer.id_customer = '. _DB_PREFIX_ . 'address.id_customer GROUP BY '. _DB_PREFIX_ . 'customer.id_customer ';
        $export_data = Db::getInstance()->executeS($query);
        $filename = 'customerdata.csv';

        $file = fopen($filename,"w");
        $header = ['id_customer', 'firstname', 'lastname', 'email', 'phone'];
        fputcsv($file,$header);
        foreach ($export_data as $line){
            fputcsv($file,$line);
        }

        if (file_exists($filename)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filename));
            readfile($filename);
            exit;
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function paginate_content( $content, $page = 1, $pagination = 10 ) {

        if( count($content) > $pagination ) {
             $content = array_slice( $content, $pagination * ($page - 1), $pagination );
        }
     
        return $content;
     
     }
}
