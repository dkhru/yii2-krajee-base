<?php

/**
 * @package   yii2-krajee-base
 * @author    Kartik Visweswaran <kartikv2@gmail.com>
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014
 * @version   1.7.2
 */

namespace kartik\base;

use Yii;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;

/**
 * Base widget class for Krajee extensions
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since  1.0
 */
class Widget extends \yii\base\Widget
{
    use TranslationTrait;

    /**
     * @var array HTML attributes or other settings for widgets
     */
    public $options = [];

    /**
     * @var array widget plugin options
     */
    public $pluginOptions = [];

    /**
     * @var array widget JQuery events. You must define events in
     * event-name => event-function format
     * for example:
     * ~~~
     * pluginEvents = [
     *     "change" => "function() { log("change"); }",
     *     "open" => "function() { log("open"); }",
     * ];
     * ~~~
     */
    public $pluginEvents = [];

    /**
     * @var array the the internalization configuration for this widget
     */
    public $i18n = [];

    /**
     * @var string translation message file category name for i18n
     */
    protected $_msgCat = '';

    /**
     * @var string the name of the jQuery plugin
     */
    protected $_pluginName;

    /**
     * @var string the hashed variable to store the pluginOptions
     */
    protected $_dataVar;

    /**
     * @var string the hashed variable to store the pluginOptions
     */
    protected $_hashVar;

    /**
     * @var string the Json encoded options
     */
    protected $_encOptions = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
    }

    /**
     * Sets HTML5 data variable
     *
     * @param string $name the plugin name
     *
     * @return void
     */
    protected function setDataVar($name)
    {
        $this->_dataVar = "data-krajee-{$name}";
    }

    /**
     * Adds an asset to the view
     *
     * @param View   $view The View object
     * @param string $file The asset file name
     * @param string $type The asset file type (css or js)
     * @param string $class The class name of the AssetBundle
     *
     * @return void
     */
    protected function addAsset($view, $file, $type, $class)
    {
        if ($type == 'css' || $type == 'js') {
            $asset = $view->getAssetManager();
            $bundle = $asset->bundles[$class];
            if ($type == 'css') {
                $bundle->css[] = $file;
            } else {
                $bundle->js[] = $file;
            }
            $asset->bundles[$class] = $bundle;
            $view->setAssetManager($asset);
        }
    }

    /**
     * Registers a specific plugin and the related events
     *
     * @param string $name the name of the plugin
     * @param string $element the plugin target element
     *
     * @return void
     */
    protected function registerPlugin($name, $element = null)
    {
        $id = ($element == null) ? "jQuery('#" . $this->options['id'] . "')" : $element;
        $view = $this->getView();
        if ($this->pluginOptions !== false) {
            $this->registerPluginOptions($name);
            $view->registerJs("{$id}.{$name}({$this->_hashVar});");
        }

        if (!empty($this->pluginEvents)) {
            $js = [];
            foreach ($this->pluginEvents as $event => $handler) {
                $function = new JsExpression($handler);
                $js[] = "{$id}.on('{$event}', {$function});";
            }
            $js = implode("\n", $js);
            $view->registerJs($js);
        }
    }

    /**
     * Registers plugin options by storing it in a hashed javascript variable
     *
     * @return void
     */
    protected function registerPluginOptions($name)
    {
        $view = $this->getView();
        $this->hashPluginOptions($name);
        $encOptions = empty($this->_encOptions) ? '{}' : $this->_encOptions;
        $view->registerJs("var {$this->_hashVar} = {$encOptions};\n", View::POS_HEAD);
    }

    /**
     * Generates a hashed variable to store the pluginOptions. The following special data attributes
     * will also be setup for the widget, that can be accessed through javascript:
     * - 'data-plugin-options' will store the hashed variable storing the plugin options.
     * - 'data-plugin-name' the name of the plugin
     *
     * @param string $name the name of the plugin
     *
     * @return void
     */
    protected function hashPluginOptions($name)
    {
        $this->_encOptions = empty($this->pluginOptions) ? '' : Json::encode($this->pluginOptions);
        $this->_hashVar = $name . '_' . hash('crc32', $this->_encOptions);
        $this->options['data-plugin-name'] = $name;
        $this->options['data-plugin-options'] = $this->_hashVar;
    }
}
