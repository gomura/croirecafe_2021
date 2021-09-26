<?php

namespace Plugin\ECCUBE4LineLoginIntegration\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class LineLoginSettingType extends AbstractType
{

    private $eccubeConfig;

    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->eccubeConfig;
        $builder
                ->add('line_channel_id', TextType::class, array(
                    'label' => 'line_channel_id',
                    'required' => false,
                    'constraints' => array(
                        new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                    ),
                ))
                ->add('line_channel_secret', TextType::class, array(
                    'label' => 'line_channel_secret',
                    'required' => false,
                    'constraints' => array(
                        new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                    ),
                ));
    }
}
