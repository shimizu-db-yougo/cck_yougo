<?php

namespace CCK\CommonBundle\Repository;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository implements UserProviderInterface
{
	public function loadUserByUsername($userId)
	{
		$user = $this->createQueryBuilder('u')
		->where('u.id = :id')
		->setParameter('id', (int) $userId)
		->getQuery()
		->getOneOrNullResult();

		if (null === $user) {
			$message = sprintf(
					'Unable to find an active admin User object identified by "%s".',
					$userId
			);
			throw new UsernameNotFoundException($message);
		}

		return $user;
	}

	public function refreshUser(UserInterface $user)
	{
		$class = get_class($user);
		if (!$this->supportsClass($class)) {
			throw new UnsupportedUserException(
					sprintf(
							'Instances of "%s" are not supported.',
							$class
					)
			);
		}

		return $this->loadUserByUsername($user->getId());
	}

	public function supportsClass($class)
	{
		return $this->getEntityName() === $class
		|| is_subclass_of($class, $this->getEntityName());
	}

}