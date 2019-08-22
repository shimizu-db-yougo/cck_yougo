<?php

namespace CCK\CommonBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CurriculumRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CurriculumRepository extends EntityRepository
{
	/**
	 * @return Ambigous <multitype:, \Doctrine\ORM\mixed, mixed, \Doctrine\DBAL\Driver\Statement, \Doctrine\Common\Cache\mixed>
	 */
	public function getCurriculumVersionList(){
		$sql = "
			SELECT
				Curriculum.id cur_id,
				Curriculum.name cur_name,
				Version.id,
				Version.name
			FROM
				Curriculum
					INNER JOIN
				Version ON (Curriculum.id = Version.curriculum_id)
			WHERE
				Curriculum.delete_flag = FALSE
				AND Version.delete_flag = FALSE
			ORDER BY Version.curriculum_id,Version.create_date";

		$result = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAll();

		return $result;
	}
}
