<?php
/**
 * Created by JetBrains PhpStorm.
 * User: manghel
 * Date: 9/19/13
 * Time: 7:45 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Ititi\ParsingBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ContentController extends Controller{

    public function homeAction() {
        return $this->render('ItitiParsingBundle:Content:home.html.twig');
    }
}