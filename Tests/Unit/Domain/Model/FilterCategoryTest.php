<?php
namespace BrainAppeal\BrainEventConnector\Tests\Unit\Domain\Model;

/**
 * Test case.
 *
 * @author Joshua Billert <joshua.billert@brain-appeal.com>
 */
class FilterCategoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getNameReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getName()
        );
    }

    /**
     * @test
     */
    public function setNameForStringSetsName()
    {
        $this->subject->setName('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'name',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getParentReturnsInitialValueForFilterCategory()
    {
        self::assertEquals(
            null,
            $this->subject->getParent()
        );
    }

    /**
     * @test
     */
    public function setParentForFilterCategorySetsParent()
    {
        $parentFixture = new \BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory();
        $this->subject->setParent($parentFixture);

        self::assertAttributeEquals(
            $parentFixture,
            'parent',
            $this->subject
        );
    }
}
