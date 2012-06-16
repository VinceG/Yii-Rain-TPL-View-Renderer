<?php
// namespace
use Rain\Tpl;
/**
 * Rain TPL view renderer
 *
 * @author Vincent Gabriel <vadimg88@gmail.com>
 * @link https://github.com/VinceG/Yii-Rain-TPL-View-Renderer
 * @link http://www.raintpl.com/
 *
 * @version 0.1
 */
class RainTPLViewRenderer extends CApplicationComponent implements IViewRenderer {
	public $fileExtension = '.tpl';
	/**
	 * is the template directory
	 */
	public $tpl_dir = null;
	/**
	 * where it save compiled templates and cache
	 */
	public $cache_dir = null;
	/**
	 * the absolute base url of your application (eg. http://www.raintpl.com)
	 */	
	public $base_url = null;
	/**
	 * enable/disable the path replace
	 */	
	public $path_replace = false;
	/**
	 * configure what to replace
	 */	
	public $path_replace_list = array();
	/**
	 *  configure what command are not allowed
	 */	
	public $black_list = array();
	/**
	 * enable/disable the control if template was modified by the last compiling
	 */	
	public $check_template_update = true;
	/**
	 * enable/disable the use of php tags in your template
	 */
	public $php_enabled = false;
	/**
	 * Use the latest beta version for rain tpl 3
	 */
	public $useRainBeta = false;
	/**
	 * Will not cache templates
	 */
	public $debug = false;
	/**
	 * Auto escape variables
	 */
	public $auto_escape = false;

    /**
     * @var Rain
     */
    private $rain;

    /**
     * Component initialization
     */
    function init(){
        Yii::import('application.extensions.raintpl.*');

		// Resolve the cache dir
		if(!$this->cache_dir) {
			// cached templates directory
	        $this->cache_dir = Yii::app()->getRuntimePath().'/raintpl/tpl_cache/';

	        // create compiled directory if not exists
	        if(!file_exists($this->cache_dir)){
	            mkdir($this->cache_dir, 0775, true);
	        }
		}

        // need this since Yii autoload handler raises an error if class is not found
        spl_autoload_unregister(array('YiiBase','autoload'));
		// Which version should we use
		if($this->useRainBeta) {
			// include
			include "rain3beta/Rain/Tpl.php";

			// config
			$config = array(
							"base_url"      => $this->base_url,
							"tpl_dir"       => $this->tpl_dir,
							"cache_dir"     => $this->cache_dir,
							"debug"         => $this->debug,
		                    "auto_escape"   => $this->auto_escape,
							"tpl_ext"   => null,
							"php_enabled"   => $this->php_enabled,
							"black_list"   => $this->black_list,
							"auto_escape"   => $this->auto_escape,
						   );

			Tpl::configure( $config );			
			// draw
			$this->rain = new Tpl;
		} else {
			// Require Rain tpl class
			include "rain2.7.2/rain.tpl.class.php";
			// Set variables
			raintpl::configure("base_url", $this->base_url );
			raintpl::configure("tpl_dir", $this->tpl_dir );
			raintpl::configure("cache_dir", $this->cache_dir );
			raintpl::configure("tpl_ext", null );
			raintpl::configure("path_replace", $this->path_replace );
			raintpl::configure("path_replace_list", $this->path_replace_list );
			raintpl::configure("black_list", $this->black_list );
			raintpl::configure("check_template_update", $this->check_template_update );
			raintpl::configure("php_enabled", $this->php_enabled );

			//initialize a Rain TPL object
			$this->rain = new RainTPL;
		}

        // adding back Yii autoload handler
        spl_autoload_register(array('YiiBase','autoload'));
    }

    /**
	 * Renders a view file.
	 * This method is required by {@link IViewRenderer}.
	 * @param CBaseController the controller or widget who is rendering the view file.
	 * @param string the view file path
	 * @param mixed the data to be passed to the view
	 * @param boolean whether the rendering result should be returned
	 * @return mixed the rendering result, or null if the rendering result is not needed.
	 */
	public function renderFile($context,$sourceFile,$data,$return) {
		// current controller properties will be accessible as {this.property}
        $data['context'] = $context;
        $data['Yii'] = Yii::app();
        $data["TIME"] = sprintf('%0.5f',Yii::getLogger()->getExecutionTime());
        $data["MEMORY"] = round(Yii::getLogger()->getMemoryUsage()/(1024*1024),2)." MB";
		
		// Configure the temlate dir
		if($this->useRainBeta) {
			Tpl::$conf['tpl_dir'] = realpath(dirname($sourceFile)) . '/';
			Tpl::configure( "tpl_dir", realpath(dirname($sourceFile)) . '/' );
		} else {
			raintpl::configure("tpl_dir", realpath(dirname($sourceFile)) . '/' );
		}
		
        // check if view file exists
        if(!is_file($sourceFile) || ($file=realpath($sourceFile))===false) {
            throw new CException(Yii::t('yiiext','View file "{file}" does not exist.', array('{file}'=>$sourceFile)));
		}
		
		//assign template...
		$this->rain->assign($data);
		
        //render or return
		if($return) {
        	return $this->rain->draw($sourceFile, true);
		} else {
			$this->rain->draw($sourceFile);
		}
	}
}