<?php

namespace Ititi\ParsingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('ItitiParsingBundle:Default:index.html.twig', array('name' => $name));
    }
}
