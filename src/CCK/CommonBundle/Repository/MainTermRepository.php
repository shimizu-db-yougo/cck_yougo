<?php

namespace CCK\CommonBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MainTermRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MainTermRepository extends EntityRepository
{
	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getYougoList($curriculum = null, $version = null, $hen = null, $sho = null, $dai = null, $chu = null, $ko = null, $nombre = null, $text_freq = null, $center_freq = null, $news_exam = null, $term = null, $sort_field = null, $sort_order = null, $term_id = null){
		$sql = "
			SELECT
				MainTerm.id,
				MainTerm.term_id,
				MainTerm.main_term,
				MainTerm.kana,
				MainTerm.index_kana,
				SubTerm.sub_term,
				SubTerm.kana sub_kana,
				SubTerm.index_kana sub_index_kana,
				ExplainIndex.index_term,
				Refer_term.refer_term,
				MainTerm.index_original,
				MainTerm.index_original_kana,
				MainTerm.index_abbreviation,
				MainTerm.handover,
				User.name,
				MainTerm.modify_date
			FROM
				MainTerm
					LEFT JOIN
				SubTerm ON (MainTerm.term_id = SubTerm.main_term_id
					AND MainTerm.delete_flag = false
					AND SubTerm.delete_flag = false)
					LEFT JOIN
				ExplainIndex ON (MainTerm.term_id = ExplainIndex.main_term_id
					AND MainTerm.delete_flag = false
					AND ExplainIndex.delete_flag = false)
					LEFT JOIN
				(SELECT M.main_term refer_term,
						R.main_term_id,
						R.delete_flag
				FROM MainTerm M
					INNER JOIN
					Refer R ON (M.term_id = R.refer_term_id
					AND M.delete_flag = false
					AND R.delete_flag = false)) Refer_term ON (MainTerm.term_id = Refer_term.main_term_id
						AND MainTerm.delete_flag = false
						AND Refer_term.delete_flag = false
					)
					INNER JOIN
				User ON (MainTerm.user_id = User.user_id
					AND MainTerm.delete_flag = false
					AND User.delete_flag = false)
					INNER JOIN
				Version ON (MainTerm.curriculum_id = Version.id
					AND MainTerm.delete_flag = false
					AND Version.delete_flag = false)
					INNER JOIN
				Curriculum ON (Version.curriculum_id = Curriculum.id
					AND Version.delete_flag = false
					AND Curriculum.delete_flag = false)
					INNER JOIN
				Header ON (MainTerm.header_id = Header.id
					AND MainTerm.delete_flag = false
					AND Header.delete_flag = false)
			WHERE
				MainTerm.delete_flag = false
		";

		if($curriculum){
			$sql .= " AND Curriculum.id = '" . str_replace("'", "''", $curriculum) . "'";
		}

		if($version){
			$sql .= " AND Version.id = '" . str_replace("'", "''", $version) . "'";
		}

		if($hen){
			$sql .= " AND Header.hen = '" . str_replace("'", "''", $hen) . "'";
		}

		if($sho){
			$sql .= " AND Header.sho = '" . str_replace("'", "''", $sho) . "'";
		}

		if($dai){
			$sql .= " AND Header.dai = '" . str_replace("'", "''", $dai) . "'";
		}

		if($chu){
			$sql .= " AND Header.chu = '" . str_replace("'", "''", $chu) . "'";
		}

		if($ko){
			$sql .= " AND Header.ko = '" . str_replace("'", "''", $ko) . "'";
		}

		if($nombre){
			$sql .= " AND MainTerm.nombre = '" . str_replace("'", "''", $nombre) . "'";
		}

		if($text_freq){
			if($text_freq == '1'){
				// A
				$sql .= " AND MainTerm.text_frequency >= 6";
			}elseif($text_freq == '2'){
				// B
				$sql .= " AND (MainTerm.text_frequency >= 3 AND MainTerm.text_frequency <= 5)";
			}else{
				// C
				$sql .= " AND MainTerm.text_frequency <= 2";
			}
		}

		if($center_freq){
			$sql .= " AND MainTerm.center_frequency = '" . str_replace("'", "''", $center_freq) . "'";
		}

		if($news_exam){
			$sql .= " AND MainTerm.news_exam = true";
		}

		if($term){
			$sql .= " AND MainTerm.main_term LIKE '%" . str_replace("'", "''", $term) . "%'";
		}

		if($term_id){
			$sql .= " AND MainTerm.id = " . $term_id;
		}

		if($sort_field){
			$sql .= " ORDER BY " . $sort_field . " " . $sort_order . " ";
		}else{
			$sql .= " ORDER BY MainTerm.print_order ";
		}

		$result = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		if(count($result) == 0){
			return $result;
		}

		$key_term_id = '';
		$wk_sub_term = array();
		$wk_sub_kana = array();
		$wk_sub_index_kana = array();
		$wk_refer_term = array();
		$wk_explain_term = array();

		$wk_result = array();
		$wk_result_record = array('id' => '','term_id' => '','main_term' => '','kana' => '','index_kana' => '','sub_term' => '','sub_kana' => '','index_term' => '','sub_index_kana' => '','refer_term' => '','index_original' => '','index_original_kana' => '','index_abbreviation' => '','handover' => '','name' => '','modify_date' => '');
		foreach($result as $result_record){
			if(($key_term_id != '')&&($result_record['term_id'] != $key_term_id)){
				// (*1)でまとめたサブ用語・指矢印用語を主用語単位にレコード生成する
				$wk_result = $this->setRecordPerMainTerm($wk_result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term, $wk_explain_term, $wk_result);

				$wk_sub_term = array();
				$wk_sub_kana = array();
				$wk_sub_index_kana = array();
				$wk_refer_term = array();
				$wk_explain_term = array();
			}

			// サブ用語・指矢印用語を主用語単位にまとめる(*1)
			$this->stackSubTerm($result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term, $wk_explain_term);

			$wk_result_record = $result_record;
			$key_term_id = $result_record['term_id'];
		}

		// サブ用語・指矢印用語を主用語単位にまとめる(*1)
		//$this->stackSubTerm($result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term, $wk_delimiter);

		// (*1)でまとめたサブ用語・指矢印用語を主用語単位にレコード生成する
		$wk_result = $this->setRecordPerMainTerm($wk_result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term, $wk_explain_term, $wk_result);

		return $wk_result;
	}

	function stackSubTerm(&$result_record, &$wk_sub_term, &$wk_sub_kana, &$wk_sub_index_kana, &$wk_refer_term, &$wk_explain_term){
		// サブ用語・指矢印用語を主用語単位にまとめる(*1)
		if((array_search($result_record['sub_term'], $wk_sub_term) === false)&&(!is_null($result_record['sub_term']))) {array_push($wk_sub_term,$result_record['sub_term']);}
		if((array_search($result_record['sub_kana'], $wk_sub_kana) === false)&&(!is_null($result_record['sub_kana']))) {array_push($wk_sub_kana,$result_record['sub_kana']);}
		if((array_search($result_record['sub_index_kana'], $wk_sub_index_kana) === false)&&(!is_null($result_record['sub_index_kana']))) {array_push($wk_sub_index_kana,$result_record['sub_index_kana']);}
		if((array_search($result_record['refer_term'], $wk_refer_term) === false)&&(!is_null($result_record['refer_term']))) {array_push($wk_refer_term,$result_record['refer_term']);}
		if((array_search($result_record['index_term'], $wk_explain_term) === false)&&(!is_null($result_record['index_term']))) {array_push($wk_explain_term,$result_record['index_term']);}
	}

	function setRecordPerMainTerm($wk_result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term, $wk_explain_term, $wk_result){
		// (*1)でまとめたサブ用語・指矢印用語を主用語単位にレコード生成する
		$wk_result_record['sub_term'] = implode('、' , $wk_sub_term);
		$wk_result_record['sub_kana'] = implode('、' , $wk_sub_kana);
		$wk_result_record['sub_index_kana'] = implode('、' , $wk_sub_index_kana);
		$wk_result_record['refer_term'] = implode('、' , $wk_refer_term);
		$wk_result_record['index_term'] = implode('、' , $wk_explain_term);

		array_push($wk_result,$wk_result_record);

		return $wk_result;
	}

	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getYougoDetail($term_id){
		$sql = "
			SELECT
				MainTerm.id,
				MainTerm.term_id,
				MainTerm.main_term,
				MainTerm.red_letter,
				MainTerm.text_frequency,
				MainTerm.center_frequency,
				MainTerm.news_exam,
				MainTerm.kana,
				MainTerm.index_kana,
				MainTerm.index_original,
				MainTerm.index_original_kana,
				MainTerm.index_abbreviation,
				MainTerm.handover,
				MainTerm.modify_date,
				MainTerm.delimiter,
				MainTerm.term_explain
			FROM
				MainTerm
			WHERE
				MainTerm.delete_flag = false
		";

		if($term_id){
			$sql .= " AND MainTerm.term_id = " . $term_id;
		}

		$result = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result[0];
	}

	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getMainTermList($cur_id,$term_id,$hen,$sho){
		$sql = "
			SELECT
				Curriculum.id cur_id,
				Curriculum.name cur_name,
				Version.id ver_id,
				Version.name var_name,
				MainTerm.id,
				MainTerm.term_id,
				MainTerm.header_id,
				MainTerm.print_order,
				MainTerm.main_term,
				MainTerm.red_letter,
				MainTerm.text_frequency,
				MainTerm.center_frequency,
				MainTerm.news_exam,
				MainTerm.kana,
				MainTerm.index_kana,
				MainTerm.index_add_letter,
				MainTerm.index_original,
				MainTerm.index_original_kana,
				MainTerm.index_abbreviation,
				MainTerm.handover,
				MainTerm.modify_date,
				MainTerm.delimiter,
				MainTerm.western_language,
				MainTerm.birth_year,
				MainTerm.nombre,
				MainTerm.illust_filename,
				MainTerm.illust_caption,
				MainTerm.illust_kana,
				MainTerm.illust_nombre,
				MainTerm.handover,
				MainTerm.term_explain
			FROM
				MainTerm
					INNER JOIN
				Version ON (MainTerm.curriculum_id = Version.id
					AND MainTerm.delete_flag = false
					AND Version.delete_flag = false)
					INNER JOIN
				Curriculum ON (Version.curriculum_id = Curriculum.id
					AND Curriculum.delete_flag = false
					AND Version.delete_flag = false)
					INNER JOIN
				Header ON (MainTerm.header_id = Header.id
					AND MainTerm.delete_flag = false
					AND Header.delete_flag = false)
				WHERE
				MainTerm.delete_flag = false
		";

		if($cur_id){
			$sql .= " AND MainTerm.curriculum_id = " . $cur_id;
		}
		if($term_id){
			$sql .= " AND MainTerm.term_id = " . $term_id;
		}
		if($hen){
			$sql .= " AND Header.hen = '" . str_replace("'", "''", $hen) . "'";
		}
		if($sho){
			$sql .= " AND Header.hen = '" . str_replace("'", "''", $hen) . "'";
			$sql .= " AND Header.sho = '" . str_replace("'", "''", $sho) . "'";
		}

		$result = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result;
	}

	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getYougoDetailOfSubterm($term_id,$is_getId=false){
		$sql = "
			SELECT
				SubTerm.id,
				SubTerm.main_term_id,
				SubTerm.sub_term,
				SubTerm.red_letter,
				SubTerm.text_frequency,
				SubTerm.center_frequency,
				SubTerm.news_exam,
				SubTerm.kana,
				SubTerm.index_kana,
				SubTerm.index_add_letter,
				SubTerm.delimiter,
				SubTerm.delimiter_kana,
				SubTerm.nombre
			FROM
				SubTerm
			WHERE
				SubTerm.delete_flag = false
		";

		if($is_getId){
			$sql .= " AND SubTerm.id = " . $term_id;
		}else{
			if($term_id){
				$sql .= " AND SubTerm.main_term_id = " . $term_id;
			}
		}

		$result_sub = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result_sub;
	}

	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getYougoDetailOfSynonym($term_id,$is_getId=false){
		$sql = "
			SELECT
				Synonym.id,
				Synonym.term,
				Synonym.synonym_id,
				Synonym.red_letter,
				Synonym.text_frequency,
				Synonym.center_frequency,
				Synonym.news_exam,
				Synonym.delimiter,
				Synonym.index_add_letter,
				Synonym.index_kana,
				Synonym.nombre
			FROM
				Synonym
			WHERE
				Synonym.delete_flag = false
		";

		if($is_getId){
			$sql .= " AND Synonym.id = " . $term_id;
		}else{
			if($term_id){
				$sql .= " AND Synonym.main_term_id = " . $term_id;
			}
		}

		$result_syn = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result_syn;
	}

	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getYougoDetailOfRefer($term_id){
		$sql = "
			SELECT
				Refer.id,
				MainTerm.main_term,
				MainTerm.header_id,
				MainTerm.curriculum_id ver_id,
				Header.hen,
				Header.sho,
				Header.dai,
				Header.chu,
				Header.ko,
				Refer.refer_term_id,
				Refer.nombre
			FROM
				MainTerm
					INNER JOIN
				Refer ON (MainTerm.term_id = Refer.refer_term_id
					AND MainTerm.delete_flag = false
					AND Refer.delete_flag = false)
					INNER JOIN
				Header ON (MainTerm.header_id = Header.id
					AND MainTerm.delete_flag = false
					AND Header.delete_flag = false)
			WHERE
				MainTerm.delete_flag = false
				AND Refer.delete_flag = false
				AND Header.delete_flag = false
		";

		if($term_id){
			$sql .= " AND Refer.main_term_id = " . $term_id;
		}

		$result_ref = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result_ref;
	}

	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getNewTermID(){
		$sql = "
			SELECT * FROM MainTerm WHERE MainTerm.term_id = (SELECT MAX(MainTerm.term_id) FROM MainTerm)
		";
		$result = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result;
	}

	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getPrintOrderList($curriculumId = null, $headerId = null){
		$sql = "
			SELECT
				*
			FROM
				MainTerm
			WHERE";
		if($curriculumId){
			$sql .= " MainTerm.curriculum_id = " . $curriculumId . " AND ";
		}
		if($headerId){
			$sql .= " MainTerm.header_id = " . $headerId . " AND ";
		}
		$sql .= " MainTerm.delete_flag = false
			ORDER BY
				MainTerm.print_order
		";
		$result = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result;
	}

	public function getYougoListByHeader($curriculum_id, $header_id){
		$sql = "
			SELECT
				MainTerm.term_id id,
				MainTerm.main_term name
			FROM
				MainTerm
			WHERE
				MainTerm.curriculum_id = ".$curriculum_id."
				AND MainTerm.header_id IN (".$header_id.")
				AND MainTerm.delete_flag = false
			ORDER BY
				MainTerm.header_id, MainTerm.print_order
		";
		$result = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result;
	}
}