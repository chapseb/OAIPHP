<?php
namespace Admin;

use \App;
use \Menu;
use \Module;
use \Sentry;
class BaseController extends \BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->data['menu_pointer'] = '<div class="pointer"><div class="arrow"></div><div class="arrow_border"></div></div>';

        $adminMenu = Menu::create('admin_sidebar');
        $dashboard = $adminMenu->createItem('dashboard', array(
            'label' => 'Tableau de bord',
            'icon'  => 'dashboard',
            'url'   => 'admin'
        ));
        $documentation = $adminMenu->createItem('Documentation', array(
            'label' => 'Documenation',
            'icon'  => 'file-text',
            'url'   => 'doc'
        ));
        $logout = $adminMenu->createItem('Logout', array(
            'label' => 'Deconnexion',
            'icon'  => 'power-off',
            'url'   => 'logout'
        ));

        $adminMenu->addItem('dashboard', $dashboard);

        $adminMenu->setActiveMenu('dashboard');

        foreach (Module::getModules() as $module) {
            $module->registerAdminMenu();
        }
        $adminMenu->addItem('documentation', $documentation);
        $adminMenu->addItem('logout', $logout);
    }
}
