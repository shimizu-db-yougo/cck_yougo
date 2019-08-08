<?php

namespace CCK\CommonBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CenterRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CenterRepository extends EntityRepository
{
	public function getCenterPoints($term_id,$yougo_flag){
		if($yougo_flag == '1'){
			// 主用語
			$kubun = 'main';
		}else{
			// サブ用語、同対類
			$kubun = 'sub';
		}

		$qb = $this->createQueryBuilder('c')
		->select('c.year')
		->addSelect('c.mainExam')
		->addSelect('c.subExam')
		->where('c.deleteFlag = :deleteFlag')
		->andWhere('c.'.$kubun.'TermId = :term_id')
		->andWhere('c.yougoFlag = :yougo_flag')
		->addOrderBy('c.year')
		->setParameters(array(
				'deleteFlag' => false,
				'term_id' => $term_id,
				'yougo_flag' => $yougo_flag
		));

		return $qb->getQuery()->getResult();
	}
}