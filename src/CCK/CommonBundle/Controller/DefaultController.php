<?php

namespace CCK\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/example")
     */
    public function indexAction()
    {
        return $this->render('CCKCommonBundle:Default:index.html.twig');
    }
}
