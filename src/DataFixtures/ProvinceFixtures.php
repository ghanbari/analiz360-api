<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ProvinceFixtures extends Fixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->exec(
            "
          INSERT INTO `province` (`id`, `name`) VALUES
            (1, 'آذربایجان شرقی'),
            (2, 'آذربایجان غربی'),
            (3, 'اردبیل'),
            (4, 'اصفهان'),
            (5, 'البرز'),
            (6, 'ایلام'),
            (7, 'بوشهر'),
            (8, 'تهران'),
            (9, 'چهارمحال وبختیاری'),
            (10, 'خراسان جنوبی'),
            (11, 'خراسان رضوی'),
            (12, 'خراسان شمالی'),
            (13, 'خوزستان'),
            (14, 'زنجان'),
            (15, 'سمنان'),
            (16, 'سیستان وبلوچستان'),
            (17, 'فارس'),
            (18, 'قزوین'),
            (19, 'قم'),
            (20, 'کردستان'),
            (21, 'کرمان'),
            (22, 'کرمانشاه'),
            (23, 'کهگیلویه وبویراحمد'),
            (24, 'گلستان'),
            (25, 'گیلان'),
            (26, 'لرستان'),
            (27, 'مازندران'),
            (28, 'مرکزی'),
            (29, 'هرمزگان'),
            (30, 'همدان'),
            (31, 'یزد');"
        );
    }

    public function getOrder()
    {
        return 1;
    }

    public static function getGroups(): array
    {
        return ['province', 'iran'];
    }
}
