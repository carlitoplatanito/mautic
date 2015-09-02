<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class CampaignEventFormSubmitType
 */
class CampaignEventFormFieldValueType extends AbstractType
{
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('form', 'form_list', array(
            'label'         => 'mautic.form.campaign.event.forms',
            'label_attr'    => array('class' => 'control-label'),
            'multiple'      => false,
            'attr'          => array(
                'class'     => 'form-control',
                'tooltip'   => 'mautic.form.campaign.event.forms_descr',
                'onchange'  => 'Mautic.updateFormFields(this)'
            )
        ));

        $formModel = $this->factory->getModel('form.form');
        $leadModel = $this->factory->getModel('lead.list');
        $operators = $leadModel->getFilterExpressionFunctions();
        $choices   = array();

        foreach ($operators as $key => $operator) {
            $choices[$key] = $operator['label'];
        }

        $builder->add('value', 'text', array(
            'label'         => 'mautic.form.field.form.value',
            'label_attr'    => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.campaign.event.form_field_value_descr'
            )
        ));

        $builder->add('operator', 'choice', array(
            'choices'  => $choices,
        ));

        $ff = $builder->getFormFactory();

        // function to add 'template' choice field dynamically
        $func = function (FormEvent $e) use ($ff, $formModel) {
            $data = $e->getData();
            $form = $e->getForm();
            $fields = array();

            if ($form->has('field')) {
                $form->remove('field');
            }

            if (empty($data['form'])) {
                $fields[] = 'Select form first';
            } else {
                $formEntity = $formModel->getEntity($data['form']);
                $formFields = $formEntity->getFields();

                foreach ($formFields as $field) {
                    if ($field->getType() != 'button') {
                        $fields[$field->getAlias()] = $field->getLabel();
                    }
                }
            }

            $form->add('field', 'choice', array(
                'choices'  => $fields,
            ));
        };

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_BIND, $func);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "campaignevent_form_field_value";
    }
}
