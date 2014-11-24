<?php

namespace Konani\VideoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class VideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('latitude', 'hidden')
            ->add('longitude', 'hidden' )
            ->add('name', 'text')
            ->add('description', 'textarea')
            ->add('save', 'submit');
    }

    public function getName()
    {
        return 'video';
    }

}