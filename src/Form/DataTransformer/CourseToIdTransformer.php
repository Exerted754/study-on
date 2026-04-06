<?php

namespace App\Form\DataTransformer;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CourseToIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private CourseRepository $courseRepository
    ) {
    }

    public function transform($course): string
    {
        if (null === $course) {
            return '';
        }

        if (!$course instanceof Course) {
            throw new TransformationFailedException('Expected a Course object.');
        }

        return (string) $course->getId();
    }

    public function reverseTransform($courseId): ?Course
    {
        if (!$courseId) {
            return null;
        }

        $course = $this->courseRepository->find($courseId);

        if (null === $course) {
            throw new TransformationFailedException(sprintf(
                'Course with id "%s" was not found.',
                $courseId
            ));
        }

        return $course;
    }
}
