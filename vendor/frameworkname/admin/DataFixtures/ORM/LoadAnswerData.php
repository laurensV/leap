<?php

namespace Smirik\QuizBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Smirik\QuizBundle\Entity\Answer;

class LoadAnswerData extends AbstractFixture implements OrderedFixtureInterface
{
  
  public function load($manager)
  {
    /**
    * Question 1 has text answer
    */
    $answer = new Answer();
    $answer->setQuestion($this->getReference('question1'));
    $answer->setTitle('Test answer 1 №1');
    $answer->setIsRight('235');
    $manager->persist($answer);
    $manager->flush();
    
    for ($i=2; $i<6; $i++)
    {
      for ($j=1; $j<5; $j++)
      {
        $answer = new Answer();
        $answer->setQuestion($this->getReference('question'.$i));
        $answer->setTitle('Test answer '.$i.' №'.$j);
        if (($j%4) == ($i%4))
        {
          $answer->setIsRight(1);
        } else
        {
          $answer->setIsRight(0);
        }
        
        $manager->persist($answer);
        $manager->flush();
      }
    }

    for ($i=1; $i<6; $i++)
    {
      for ($j=1; $j<5; $j++)
      {
        $answer = new Answer();
        $answer->setQuestion($this->getReference('t_question'.$i));
        $answer->setTitle('Test no time answer '.$i.' №'.$j);
        if (($j%4) == ($i%4))
        {
          $answer->setIsRight(1);
        } else
        {
          $answer->setIsRight(0);
        }
        
        $manager->persist($answer);
        $manager->flush();
      }
    }    
  }
  
  public function getOrder()
  {
    return 5;
  }
  
}