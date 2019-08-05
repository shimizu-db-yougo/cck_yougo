<?php
namespace CCK\ClientBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use CCK\ClientBundle\Controller\BaseController;

class ClientExtension extends \Twig_Extension
{
	/**
	 * @var unknown
	 */
	private $container;

	/**
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container){
		$this->container = $container;
	}

	/* (non-PHPdoc)
	 * @see Twig_Extension::getFilters()
	 */
	public function getFilters()
	{
		return array(
				new \Twig_SimpleFilter('masters', array($this, 'masterFilter'))
		);
	}

	/**
	 * @param unknown $key
	 * @param unknown $target
	 * @return Ambiguous
	 */
	public function masterFilter($target, $key){
		if(!$this->container->hasParameter('master.'. $key)) return $target;
		$masters = $this->container->getParameter('master.' . $key);
		if(!isset($masters[$target])) return $target;
		return $masters[$target];
	}

	/* (non-PHPdoc)
	 * @see Twig_Extension::getFunctions()
	 */
	public function getFunctions(){
		return array(
			new \Twig_SimpleFunction('getTextFreqIcon', array($this, 'getTextFreqIcon')),
			new \Twig_SimpleFunction('getCenterFreqIcon', array($this, 'getCenterFreqIcon')),
			new \Twig_SimpleFunction('getSynonymIcon', array($this, 'getSynonymIcon')),
			new \Twig_SimpleFunction('getNewsExamIcon', array($this, 'getNewsExamIcon'))
		);
	}

	/**
	 * [getTextFreq description]
	 * @param  [type] $textFreq     [description]
	 * @return [type]               [description]
	 */
	public function getTextFreqIcon($textFreq){
		if($textFreq == "") return "";

		if($textFreq >= 6){
			$text_freq_tag = '<img src="/./img/A.jpg" class="icon-full" alt="A">';
		}elseif(($textFreq >= 3) && ($textFreq <= 5)){
			$text_freq_tag = '<img src="/./img/B.jpg" class="icon-full" alt="B">';
		}elseif(($textFreq >= 1) && ($textFreq <= 2)){
			$text_freq_tag = '<img src="/./img/C.jpg" class="icon-full" alt="C">';
		}else{
			$text_freq_tag = '';
		}

		return $text_freq_tag;
	}

	/**
	 * [getCenterFreqIcon description]
	 * @param  [type] $centerFreq     [description]
	 * @return [type]               [description]
	 */
	public function getCenterFreqIcon($centerFreq){
		if($centerFreq == "") return "";

		if(($centerFreq > 0) && ($centerFreq < 10)){
			$center_freq_tag = '<img src="/./img/'.$centerFreq.'.jpg" class="icon-full" alt="'.$centerFreq.'">';
		}elseif($centerFreq >= 10){
			$center_freq_tag = '<img src="/./img/'.substr($centerFreq,0,1).'_.jpg" class="icon-half" alt="'.substr($centerFreq,0,1).'_"><img src="/./img/_'.substr($centerFreq,1).'.jpg" class="icon-half" alt="_'.substr($centerFreq,1).'">';
		}else{
			$center_freq_tag = '';
		}

		return $center_freq_tag;
	}

	/**
	 * [getSynonymIcon description]
	 * @param  [type] $synonym_id     [description]
	 * @return [type]               [description]
	 */
	public function getSynonymIcon($synonym_id){
		if($synonym_id == "") return "";

		if($synonym_id == '1'){
			$img_name = 'dou';
		}elseif($synonym_id == '2'){
			$img_name = 'tai';
		}else{
			$img_name = 'rui';
		}

		$synonym_tag = '<img src="/./img/'.$img_name.'.jpg" class="icon-full" alt="'.$img_name.'">';

		return $synonym_tag;
	}

	/**
	 * [getNewsExamIcon description]
	 * @param  [type] $news_exam     [description]
	 * @return [type]               [description]
	 */
	public function getNewsExamIcon($news_exam){
		if($news_exam == "1"){
			return '<img src="/./img/N.jpg" class="icon-full" alt="N">';
		}else{
			return "";
		}
	}

	public function twigFileExists($filename){
		return file_exists($filename);
	}

	public function getName()
	{
		return 'client_extension';
	}
}