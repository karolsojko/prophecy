<?php

namespace spec\Prophecy\Doubler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DoublerSpec extends ObjectBehavior
{
    /**
     * @param Prophecy\Doubler\Generator\ClassMirror  $mirror
     * @param Prophecy\Doubler\Generator\ClassCreator $creator
     * @param Prophecy\Doubler\NameGenerator  $namer
     */
    function let($mirror, $creator, $namer)
    {
        $this->beConstructedWith($mirror, $creator, $namer);
    }

    function it_does_not_have_patches_by_default()
    {
        $this->getClassPatches()->shouldHaveCount(0);
    }

    /**
     * @param Prophecy\Doubler\ClassPatch\ClassPatchInterface $patch
     */
    function its_registerClassPatch_adds_a_patch_to_the_doubler($patch)
    {
        $this->registerClassPatch($patch);
        $this->getClassPatches()->shouldReturn(array($patch));
    }

    /**
     * @param Prophecy\Doubler\ClassPatch\ClassPatchInterface $alt1
     * @param Prophecy\Doubler\ClassPatch\ClassPatchInterface $alt2
     * @param Prophecy\Doubler\ClassPatch\ClassPatchInterface $alt3
     * @param Prophecy\Doubler\ClassPatch\ClassPatchInterface $alt4
     */
    function its_getClassPatches_sorts_patches_by_priority($alt1, $alt2, $alt3, $alt4)
    {
        $alt1->getPriority()->willReturn(2);
        $alt2->getPriority()->willReturn(50);
        $alt3->getPriority()->willReturn(10);
        $alt4->getPriority()->willReturn(0);

        $this->registerClassPatch($alt1);
        $this->registerClassPatch($alt2);
        $this->registerClassPatch($alt3);
        $this->registerClassPatch($alt4);

        $this->getClassPatches()->shouldReturn(array($alt2, $alt3, $alt1, $alt4));
    }

    /**
     * @param Prophecy\Doubler\ClassPatch\ClassPatchInterface $alt1
     * @param Prophecy\Doubler\ClassPatch\ClassPatchInterface $alt2
     * @param ReflectionClass                                $class
     * @param ReflectionClass                                $interface1
     * @param ReflectionClass                                $interface2
     * @param Prophecy\Doubler\Generator\Node\ClassNode              $node
     * @param stdClass                                       $object
     */
    function its_double_mirrors_alterates_and_instantiates_provided_class(
        $mirror, $creator, $namer, $alt1, $alt2, $class, $interface1, $interface2, $node, $object
    )
    {
        $mirror->reflect($class, array($interface1, $interface2))->willReturn($node);
        $alt1->supports($node)->willReturn(true);
        $alt2->supports($node)->willReturn(false);
        $alt1->getPriority()->willReturn(1);
        $alt2->getPriority()->willReturn(2);
        // $creator->initialize(Argument::any())->willReturn($object);
        $namer->name($class, array($interface1, $interface2))->willReturn('SplStack');
        $class->getName()->willReturn('stdClass');
        $interface1->getName()->willReturn('ArrayAccess');
        $interface2->getName()->willReturn('Iterator');

        $alt1->apply($node)->shouldBeCalled();
        $alt2->apply($node)->shouldNotBeCalled();
        $creator->create('SplStack', $node)->shouldBeCalled();

        $this->registerClassPatch($alt1);
        $this->registerClassPatch($alt2);

        $this->double($class, array($interface1, $interface2))
            ->shouldReturnAnInstanceOf('SplStack');
    }
}
