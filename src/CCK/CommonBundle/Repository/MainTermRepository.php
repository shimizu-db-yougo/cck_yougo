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
	public function getYougoList($curriculum = null, $version = null, $hen = null, $sho = null, $dai = null, $chu = null, $ko = null, $nombre = null, $text_freq = null, $center_freq = null, $news_exam = null, $term = null){
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
			$sql .= " AND MainTerm.text_frequency = '" . str_replace("'", "''", $text_freq) . "'";
		}

		if($center_freq){
			$sql .= " AND MainTerm.center_frequency = '" . str_replace("'", "''", $center_freq) . "'";
		}

		if($news_exam){
			$sql .= " AND MainTerm.news_exam = '" . str_replace("'", "''", $news_exam) . "'";
		}

		if($term){
			$sql .= " AND MainTerm.main_term = '" . str_replace("'", "''", $term) . "'";
		}

		$sql .= " ORDER BY MainTerm.print_order ";

		$result = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		$key_term_id = '';
		$wk_sub_term = array();
		$wk_sub_kana = array();
		$wk_sub_index_kana = array();
		$wk_refer_term = array();
		$wk_result = array();
		foreach($result as $result_record){
			if(($key_term_id != '')&&($result_record['term_id'] != $key_term_id)){
				// (*1)でまとめたサブ用語・指矢印用語を主用語単位にレコード生成する
				$wk_result = $this->setRecordPerMainTerm($wk_result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term, $wk_result);

				$wk_sub_term = array();
				$wk_sub_kana = array();
				$wk_sub_index_kana = array();
				$wk_refer_term = array();
			}

			// サブ用語・指矢印用語を主用語単位にまとめる(*1)
			$this->stackSubTerm($result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term);

			$wk_result_record = $result_record;
			$key_term_id = $result_record['term_id'];
		}

		// サブ用語・指矢印用語を主用語単位にまとめる(*1)
		$this->stackSubTerm($result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term);

		// (*1)でまとめたサブ用語・指矢印用語を主用語単位にレコード生成する
		$wk_result = $this->setRecordPerMainTerm($wk_result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term, $wk_result);

		return $wk_result;
	}

	function stackSubTerm(&$result_record, &$wk_sub_term, &$wk_sub_kana, &$wk_sub_index_kana, &$wk_refer_term){
		// サブ用語・指矢印用語を主用語単位にまとめる(*1)
		if((array_search($result_record['sub_term'], $wk_sub_term) === false)&&(!is_null($result_record['sub_term']))) {array_push($wk_sub_term,$result_record['sub_term']);}
		if((array_search($result_record['sub_kana'], $wk_sub_kana) === false)&&(!is_null($result_record['sub_kana']))) {array_push($wk_sub_kana,$result_record['sub_kana']);}
		if((array_search($result_record['sub_index_kana'], $wk_sub_index_kana) === false)&&(!is_null($result_record['sub_index_kana']))) {array_push($wk_sub_index_kana,$result_record['sub_index_kana']);}
		if((array_search($result_record['refer_term'], $wk_refer_term) === false)&&(!is_null($result_record['refer_term']))) {array_push($wk_refer_term,$result_record['refer_term']);}
	}

	function setRecordPerMainTerm($wk_result_record, $wk_sub_term, $wk_sub_kana, $wk_sub_index_kana, $wk_refer_term, $wk_result){
		// (*1)でまとめたサブ用語・指矢印用語を主用語単位にレコード生成する
		$wk_result_record['sub_term'] = implode('、' , $wk_sub_term);
		$wk_result_record['sub_kana'] = implode('、' , $wk_sub_kana);
		$wk_result_record['sub_index_kana'] = implode('、' , $wk_sub_index_kana);
		$wk_result_record['refer_term'] = implode('、' , $wk_refer_term);

		array_push($wk_result,$wk_result_record);

		return $wk_result;
	}

}
