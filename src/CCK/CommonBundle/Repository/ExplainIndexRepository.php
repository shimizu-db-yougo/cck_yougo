<?php

namespace CCK\CommonBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ExplainIndexRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ExplainIndexRepository extends EntityRepository
{
	public function getExplainTerms($term_id){
		$qb = $this->createQueryBuilder('c')
		->select('c.indexTerm')
		->addSelect('c.id')
		->addSelect('c.indexAddLetter')
		->addSelect('c.indexKana')
		->addSelect('c.nombre')
		->where('c.deleteFlag = :deleteFlag')
		->andWhere('c.mainTermId = :term_id')
		->addOrderBy('c.indexKana')
		->setParameters(array(
				'deleteFlag' => false,
				'term_id' => $term_id
		));

		return $qb->getQuery()->getResult();
	}
}
