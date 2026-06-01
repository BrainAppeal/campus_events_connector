<?php

declare(strict_types=1);

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2026 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Domain\Model;

/**
 * EventTicketPriceVariant
 */
class EventTicketPriceVariant extends AbstractImportedEntity implements BelongsToEventInterface
{
    /**
     * Event
     *
     * @var ?Event
     */
    protected ?Event $event = null;

    /**
     * bookableFrom
     *
     * @var ?\DateTime
     */
    protected ?\DateTime $bookableFrom = null;

    /**
     * bookableTill
     *
     * @var ?\DateTime
     */
    protected ?\DateTime $bookableTill = null;

    /**
     * quota
     *
     * @var ?int
     */
    protected ?int $pvQuota = null;

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * price
     *
     * @var ?float
     */
    protected ?float $pvPrice = null;

    /**
     * taxRate
     *
     * @var ?float
     */
    protected ?float $pvTaxRate = null;

    /**
     * tax
     *
     * @var ?float
     */
    protected ?float $pvTax = null;

    /**
     * directCheckoutUrl
     *
     * @var string
     */
    protected $directCheckoutUrl = '';

    /**
     * Price category
     *
     * @var ?PriceCategory
     */
    protected ?PriceCategory $priceCategory = null;

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    /**
     * @return ?\DateTime
     */
    public function getBookableFrom(): ?\DateTime
    {
        return $this->bookableFrom;
    }

    /**
     * @param ?\DateTime $bookableFrom
     */
    public function setBookableFrom(?\DateTime $bookableFrom): void
    {
        $this->bookableFrom = $bookableFrom;
    }

    /**
     * @return ?\DateTime
     */
    public function getBookableTill(): ?\DateTime
    {
        return $this->bookableTill;
    }

    /**
     * @param ?\DateTime $bookableTill
     */
    public function setBookableTill(?\DateTime $bookableTill): void
    {
        $this->bookableTill = $bookableTill;
    }

    /**
     * @return ?int
     */
    public function getQuota(): ?int
    {
        return $this->pvQuota;
    }

    /**
     * @return ?int
     */
    public function getPvQuota(): ?int
    {
        return $this->pvQuota;
    }

    /**
     * @param ?int $pvQuota
     */
    public function setPvQuota(?int $pvQuota): void
    {
        $this->pvQuota = $pvQuota;
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
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return ?float
     */
    public function getPrice(): ?float
    {
        return $this->pvPrice;
    }

    /**
     * @return ?float
     */
    public function getPvPrice(): ?float
    {
        return $this->pvPrice;
    }

    /**
     * @param ?float $pvPrice
     */
    public function setPvPrice(?float $pvPrice): void
    {
        $this->pvPrice = $pvPrice;
    }

    /**
     * @return ?float
     */
    public function getTaxRate(): ?float
    {
        return $this->pvTaxRate;
    }

    /**
     * @return ?float
     */
    public function getPvTaxRate(): ?float
    {
        return $this->pvTaxRate;
    }

    /**
     * @param ?float $pvTaxRate
     */
    public function setPvTaxRate(?float $pvTaxRate): void
    {
        $this->pvTaxRate = $pvTaxRate;
    }

    /**
     * @return ?float
     */
    public function getTax(): ?float
    {
        return $this->pvTax;
    }

    /**
     * @return ?float
     */
    public function getPvTax(): ?float
    {
        return $this->pvTax;
    }

    /**
     * @param ?float $pvTax
     */
    public function setPvTax(?float $pvTax): void
    {
        $this->pvTax = $pvTax;
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
    public function setDirectCheckoutUrl($directCheckoutUrl): void
    {
        $this->directCheckoutUrl = $directCheckoutUrl;
    }

    public function getPriceCategory(): ?PriceCategory
    {
        return $this->priceCategory;
    }

    public function setPriceCategory(?PriceCategory $priceCategory): void
    {
        $this->priceCategory = $priceCategory;
    }

}
