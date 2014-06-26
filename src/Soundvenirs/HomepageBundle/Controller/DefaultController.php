<?php

namespace Soundvenirs\HomepageBundle\Controller;

use Soundvenirs\DomainBundle\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $form = $this->createForm('form')
            ->add('title', 'text')
            ->add('soundfile', 'file', array('attr' => array('onchange' => 'this.form.submit()')));

        return $this->render('SoundvenirsHomepageBundle:Default:index.html.twig', array('form' => $form->createView()));
    }

    public function uploadAction(Request $request)
    {
        $form = $this->createForm('form')
            ->add('title', 'text')
            ->add('soundfile', 'file');
        $form->submit($request);
        $files = $request->files->get($form->getName());
        $soundfile = $files['soundfile'];
        $data = $form->getData();
        $title = $data['title'];
        $extension = pathinfo($soundfile->getClientOriginalName(), PATHINFO_EXTENSION);
        if ($extension !== 'mp3') {
            return new Response('Only mp3 files are allowed.', 500);
        }
        if (is_null($title)) {
            $title = \basename($soundfile->getClientOriginalName(), '.mp3');
        }
        $soundRepository = $this->get('soundvenirs_domain.sound_repository');
        $soundFactory = new Factory\Sound($soundRepository);
        $sound = $soundFactory->create();
        $sound->title = $title;

        $em = $this->getDoctrine()->getManager();
        $em->persist($sound);
        $em->flush();

        $soundfile->move('/var/tmp/', 'soundvenirs-'.$sound->id.'.mp3');
        return $this->render('SoundvenirsHomepageBundle:Default:qrcode.html.twig', array('id' => $sound->id));
    }
}