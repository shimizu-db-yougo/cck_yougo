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
			new \Twig_SimpleFunction('getNewsExamIcon', array($this, 'getNewsExamIcon')),
			new \Twig_SimpleFunction('replaceTag', array($this, 'replaceTag')),
			new \Twig_SimpleFunction('getDelimiter', array($this, 'getDelimiter')),
			new \Twig_SimpleFunction('getDelimiterSyn', array($this, 'getDelimiterSyn')),
			new \Twig_SimpleFunction('getTerm', array($this, 'getTerm')),
			new \Twig_SimpleFunction('getKana', array($this, 'getKana')),
		);
	}

	/**
	 * [getTextFreq description]
	 * @param  [type] $textFreq     [description]
	 * @return [type]               [description]
	 */
	public function getTextFreqIcon($textFreq,$ranka,$rankb){
		if($textFreq == "") return "";

		if($textFreq >= $ranka){
			$text_freq_tag = '<img src="/./img/A.jpg" class="icon-full" alt="A">';
		}elseif(($textFreq >= $rankb) && ($textFreq <= ($ranka-1))){
			$text_freq_tag = '<img src="/./img/B.jpg" class="icon-full" alt="B">';
		}elseif(($textFreq >= 1) && ($textFreq <= ($rankb-1))){
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

	/**
	 * [replaceTag description]
	 * @param  [type] $term_explain     [description]
	 * @return [type]               [description]
	 */
	public function replaceTag($term_explain){
		$term_explain = str_replace('《rtn》', '<br>', $term_explain);
		$term_explain = str_replace('《c_SI》', '<img src="/./img/shiryo.jpg" class="icon-3times" alt="shiryo">', $term_explain);
		$term_explain = preg_replace('/《c_G》(.*?)《\/c_G》/u', '<span style="font-weight: bold;">$1</span>', $term_explain);
		$term_explain = preg_replace('/《c_SY》(.*?)《\/c_SY》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_SK》(.*?)《\/c_SK》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_KJ》(.*?)《\/c_KJ》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_KA》(.*?)《\/c_KA》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_GAI》(.*?)《\/c_GAI》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_UT》(.*?)《\/c_UT》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_ST》(.*?)《\/c_ST》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_UBK》(.*?)《\/c_UBK》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_TM:[0-9]+》(.*?)《\/c_TM》/u', '$1', $term_explain);
		$term_explain = preg_replace('/《c_SAK》(.*?)《\/c_SAK》/u', '$1', $term_explain);
		$term_explain = preg_replace('/【.*?】/', '', $term_explain);

		return $term_explain;
	}

	/**
	 * [getDelimiter description]
	 * @param  [type] $delimiter     [description]
	 * @return [type]               [description]
	 */
	public function getDelimiter($delimiter){
		if($delimiter == "1"){
			return "delimiter.to";
		}elseif($delimiter == "2"){
			return "delimiter.comma";
		}elseif($delimiter == "3"){
			return "delimiter.nakaguro";
		}elseif($delimiter == "4"){
			return "delimiter.slash";
		}elseif($delimiter == "5"){
			return "delimiter.paren_start";
		}elseif($delimiter == "6"){
			return "delimiter.paren_end";
		}elseif($delimiter == "7"){
			return "delimiter.paren_end";
		}elseif($delimiter == "8"){
			return "delimiter.paren_end_to";
		}else{
			return "";
		}
	}

	/**
	 * [getDelimiter description]
	 * @param  [type] $delimiter     [description]
	 * @return [type]               [description]
	 */
	public function getDelimiterSyn($delimiter){
		if($delimiter == "1"){
			return "delimiter.full_space";
		}elseif($delimiter == "2"){
			return "delimiter.paren_start";
		}elseif($delimiter == "3"){
			return "delimiter.paren_end";
		}elseif($delimiter == "4"){
			return "delimiter.newline";
		}elseif($delimiter == "5"){
			return "delimiter.newline_paren_start";
		}elseif($delimiter == "6"){
			return "delimiter.newline_paren_end";
		}else{
			return "";
		}
	}

	/**
	 * [getTerm description]
	 * @param  [type] $term     [description]
	 * @return [type]               [description]
	 */
	public function getTerm($term){
		$rtn = str_replace('【', '<span style="font-size: 10px; font-family: serif; font-weight: normal;">', $term);
		$rtn = str_replace('】', '</span>', $rtn);
		return $rtn;
	}

	/**
	 * [getKana description]
	 * @param  [type] $term     [description]
	 * @return [type]               [description]
	 */
	public function getKana($term){
		$rtn = '';
		if ( preg_match('/【.*?】/', $term, $matches)){
			$rtn = $matches[0];
			$rtn = str_replace('【', '', $rtn);
			$rtn = str_replace('】', '', $rtn);
		}
		return $rtn;
	}

	public function twigFileExists($filename){
		return file_exists($filename);
	}

	public function getName()
	{
		return 'client_extension';
	}
}