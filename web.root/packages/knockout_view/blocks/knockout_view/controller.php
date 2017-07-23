<?php
namespace Concrete\Package\KnockoutView\Block\KnockoutView;

use \Tops\sys\TSession;
use Concrete\Core\Block\BlockController;
use Page;
use Tops\ui\TViewModelManager;

class Controller extends BlockController
{
    protected $btTable = 'btKnockoutView';
    protected $btInterfaceWidth = "600";
    protected $btWrapperClass = 'ccm-ui';
    protected $btInterfaceHeight = "500";

    protected $btCacheBlockRecord = false;
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputOnPost = false;
    protected $btCacheBlockOutputForRegisteredUsers = false;

/*
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputForRegisteredUsers = true;
*/
    protected $btIgnorePageThemeGridFrameworkContainer = true;


    public $content = "";
    public $viewmodel = '';
    public $addwrapper = '';

    public function getBlockTypeDescription()
    {
        return t("Create a Knockout View");
    }

    public function getBlockTypeName()
    {
        return t("Knockout View");
    }
    
    private function getPathSettings() {
        $result = \Peanut\Bootstrap::getSettings();
        /*
        $iniPath = DIR_APPLICATION.'/config/settings.ini';
        $settings = parse_ini_file($iniPath);
        $result = new \stdClass();
        $root = DIR_BASE . '/';
        $result->peanutPath =  $root . (empty($settings['peanutRootPath']) ? 'pnut' : $settings['peanutRootPath']);
        $result->mvvmPath =  $root . (empty($settings['mvvmPath']) ? 'application/mvvm' : $settings['mvvmPath']);
        $result->corePath =  $root . (empty($settings['corePath']) ? 'pnut/core' : $settings['corePath']);
        $result->modulePath =  $root . (empty($settings['modulePath']) ? 'packages' : $settings['modulePath']);
        */
        return $result;
    }

    /**
     * Return structure parsed form $this->viewModel
     * $result->code  -  view model code name
     * $result->path  - location of javascript file
     *
     * Format of $this viewmodel is
     *    [package handle::][subpath\]viewmodel_code
     *
     *  Example
     *      $this->viewmodel == 'myModel';
     *      returns
     *              code: 'myModel';
     *              path: '/application/js/vm/myModelViewModel.js
     *
     *      $this->viewmodel == 'aftm::myModel';
     *      returns
     *              code: 'myModel';
     *              path: '/packages/aftm/js/vm/myModelViewModel.js
     *
     *      $this->viewmodel == 'users/admi/myModel';
     *      returns
     *              code: 'myModel';
     *              path: '/application/js/vm/myModelViewModel.js
     *
     *      $this->viewmodel == 'aftm::users/admin/myModel';
     *      returns
     *              code: 'myModel';
     *              path: '/packages/aftm/js/vm/users/admin/myModelViewModel.js
     *
     *
     * @return bool|\stdClass
     * false if $this->viewModel not assigned.
     */
    private function getViewModel()
    {
        $result = new \stdClass();
        $result->wrapperid = '';
        $result->vmname = '';
        $result->viewfile = '';

        if (empty($this->viewmodel)) {
            return $result;
        }

        $paths = $this->getPathSettings();
        $vmcode = $this->viewmodel;
        
        $parts = explode('/',$vmcode);
        $defaultPath = $paths->mvvmPath;
        
        if ($parts[0] == '') {
            // assume hard coded path if name starts with '/'
            $vmname = array_pop($parts);
            $vmroot = join('/',$parts) . '/';
        }
        else {
            switch ($parts[0]) {
                case '@pnut' :
                    $pathRoot = $paths->peanutRootPath;
                    array_shift($parts);
                    break;
                case '@core' :
                    $pathRoot = $paths->corePath;
                    array_shift($parts);
                    break;
                case '@app'  :
                    $pathRoot = $paths->mvvmPath;
                    array_shift($parts);
                    break;
                case  '@module' :
                    $subDir = array_shift($parts);
                    $pathRoot = sprintf($paths->modulePath,$subDir);
                    array_shift($parts);
                    break;
                default:
                    $pathRoot = $paths->mvvmPath;
                    break;
            }

            $vmname = array_pop($parts);
            $vmroot = empty($parts) ? $pathRoot : $pathRoot . join('/',$parts) . '/';

        }
                

        $result->viewfile = $vmroot.'/view/'.$vmname.'.html';
        $result->vmname = $vmname;
        $result->wrapperid = strtolower($vmname)."-view-container";

        return $result;
    }

    private static $randkeys;

    private function random_string($length) {
        $key = '';
        if (empty($this->randkeys)) {
            $this->randkeys = array_merge(range(0, 9), range('a', 'z'));
        }
        $keys = $this->randkeys;

        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }

        return $key;
    }


    public function view()
    {
        $vmInfo = TViewModelManager::getViewModelSettings($this->viewmodel);
        if (empty($vmInfo)) {
            $this->set('content',"<h2>Error: Cannot find view model configuration for '$this->viewmodel'</h2>");
            $this->set('viewcontainerid', '');
            $this->set('addwrapper', false);
        } else {
            $c = Page::getCurrentPage();
            if (!$c->isEditMode()) {
                $this->requireAsset('javascript', 'headjs');
                $this->requireAsset('javascript', 'knockoutjs');
                $this->requireAsset('javascript', 'peanut');

                $this->requireAsset('javascript', 'underscore');
                $this->requireAsset('css', 'jquery/ui');
                $this->requireAsset('javascript', 'jquery/ui');

                if ($vmInfo->view != 'content') {
                    $content = @file_get_contents($vmInfo->view);
                    $content = file_get_contents(DIR_BASE . '/' . $vmInfo->view);
                    if ($content === FALSE) {
                        $this->content = "<p>Warning: View file not found: $vmInfo->view </p>";
                    } else {
                        $this->content = $content;
                    }
                }
            }

            $containerId = strtolower(array_pop(explode('/',$vmInfo->vmName)))."-view-container";

            $this->set('content', $this->content);
            $this->set('viewcontainerid', $containerId);
            $this->set('addwrapper', $this->addwrapper);

            if (!$c->isEditMode()) {
                $this->addFooterItem(
                    TViewModelManager::GetStartScript());

                // init security token
                TSession::Initialize();
            }

        }
    }

    public function add()
    {
        $this->edit();
    }

    public function edit()
    {
        $this->requireAsset('ace');
    }

    public function getSearchableContent()
    {
        return $this->content;
    }

    public function save($data)
    {
        $content = isset($data['content']) ? $data['content'] : '';
        $args['content'] = isset($data['content']) ? $data['content'] : '';
        $args['viewmodel'] = isset($data['viewmodel']) ? $data['viewmodel'] : '';
        $args['addwrapper'] = isset($data['addwrapper']) ? $data['addwrapper'] : 0;
        parent::save($args);
    }

    public static function xml_highlight($s)
    {
        $s = htmlspecialchars($s);
        $s = preg_replace(
            "#&lt;([/]*?)(.*)([\s]*?)&gt;#sU",
            "<font color=\"#0000FF\">&lt;\\1\\2\\3&gt;</font>",
            $s
        );
        $s = preg_replace(
            "#&lt;([\?])(.*)([\?])&gt;#sU",
            "<font color=\"#800000\">&lt;\\1\\2\\3&gt;</font>",
            $s
        );
        $s = preg_replace(
            "#&lt;([^\s\?/=])(.*)([\[\s/]|&gt;)#iU",
            "&lt;<font color=\"#808000\">\\1\\2</font>\\3",
            $s
        );
        $s = preg_replace(
            "#&lt;([/])([^\s]*?)([\s\]]*?)&gt;#iU",
            "&lt;\\1<font color=\"#808000\">\\2</font>\\3&gt;",
            $s
        );
        $s = preg_replace(
            "#([^\s]*?)\=(&quot;|')(.*)(&quot;|')#isU",
            "<font color=\"#800080\">\\1</font>=<font color=\"#FF00FF\">\\2\\3\\4</font>",
            $s
        );
        $s = preg_replace(
            "#&lt;(.*)(\[)(.*)(\])&gt;#isU",
            "&lt;\\1<font color=\"#800080\">\\2\\3\\4</font>&gt;",
            $s
        );

        return nl2br($s);
    }
}
