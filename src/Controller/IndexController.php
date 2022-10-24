<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;

class IndexController extends AbstractController
{
    public function index(MailerInterface $mailer): Response
    {
        return $this->render('index.html.twig', []);
    }
}