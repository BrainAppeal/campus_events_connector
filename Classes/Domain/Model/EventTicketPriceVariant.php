<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2019 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */


namespace BrainAppeal\CampusEventsConnector\Domain\Model;

/**
 * EventTicketPriceVariant
 */
class EventTicketPriceVariant extends AbstractImportedEntity
{

    /**
     * bookableFrom
     *
     * @var \DateTime
     */
    protected $bookableFrom = null;

    /**
     * bookableTill
     *
     * @var \DateTime
     */
    protected $bookableTill = null;

    /**
     * quota
     *
     * @var string
     */
    protected $quota = '';

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * price
     *
     * @var string
     */
    protected $price = '';

    /**
     * taxRate
     *
     * @var string
     */
    protected $taxRate = '';

    /**
     * tax
     *
     * @var string
     */
    protected $tax = '';

    /**
     * directCheckoutUrl
     *
     * @var string
     */
    protected $directCheckoutUrl = '';

    /**
     * @return \DateTime
     */
    public function getBookableFrom()
    {
        return $this->bookableFrom;
    }

    /**
     * @param \DateTime $bookableFrom
     */
    public function setBookableFrom($bookableFrom)
    {
        $this->bookableFrom = $bookableFrom;
    }

    /**
     * @return \DateTime
     */
    public function getBookableTill()
    {
        return $this->bookableTill;
    }

    /**
     * @param \DateTime $bookableTill
     */
    public function setBookableTill($bookableTill)
    {
        $this->bookableTill = $bookableTill;
    }

    /**
     * @return string
     */
    public function getQuota()
    {
        return $this->quota;
    }

    /**
     * @param string $quota
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @param string $taxRate
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
    }

    /**
     * @return string
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param string $tax
     */
    public function setTax($tax)
    {
        $this->tax = $tax;
    }

    /**
     * @return string
     */
    public function getDirectCheckoutUrl()
    {
        return $this->directCheckoutUrl;
    }

    /**
     * @param string $directCheckoutUrl
     */
    public function setDirectCheckoutUrl($directCheckoutUrl)
    {
        $this->directCheckoutUrl = $directCheckoutUrl;
    }
}
