<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use App\Entity\Media;
use App\Form\MediaType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateMediaAction
{
    private $validator;
    private $doctrine;
    private $factory;

    public function __construct(RegistryInterface $doctrine, FormFactoryInterface $factory, ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->doctrine = $doctrine;
        $this->factory = $factory;
    }

    /**
     * @IsGranted("ROLE_USER")
     *
     * @param Request $request
     *
     * @return Media
     */
    public function __invoke(Request $request): Media
    {
        $media = new Media();

        $form = $this->factory->create(MediaType::class, $media);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($media);
            $em->flush();

            // Prevent the serialization of the file property
            $media->setFile(null);

            return $media;
        }

        // This will be handled by API Platform and returns a validation error.
        throw new ValidationException($this->validator->validate($media));
    }
}
