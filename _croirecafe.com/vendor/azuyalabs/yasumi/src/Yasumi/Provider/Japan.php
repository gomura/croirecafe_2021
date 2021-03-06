<?php

/**
 * This file is part of the Yasumi package.
 *
 * Copyright (c) 2015 - 2019 AzuyaLabs
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Sacha Telgenhof <me@sachatelgenhof.com>
 */

namespace Yasumi\Provider;

use DateInterval;
use DateTime;
use DateTimeZone;
use Yasumi\Holiday;

/**
 * Provider for all holidays in the Japan.
 */
class Japan extends AbstractProvider
{
    use CommonHolidays;

    /**
     * Code to identify this Holiday Provider. Typically this is the ISO3166 code corresponding to the respective
     * country or sub-region.
     */
    public const ID = 'JP';

    /**
     * The gradient parameter of the approximate expression to calculate equinox day.
     */
    private const EQUINOX_GRADIENT = 0.242194;

    /**
     * The initial parameter of the approximate expression to calculate vernal equinox day from 1900 to 1979.
     */
    private const VERNAL_EQUINOX_PARAM_1979 = 20.8357;

    /**
     * The initial parameter of the approximate expression to calculate vernal equinox day from 1980 to 2099.
     */
    private const VERNAL_EQUINOX_PARAM_2099 = 20.8431;

    /**
     * The initial parameter of the approximate expression to calculate vernal equinox day from 2100 to 2150.
     */
    private const VERNAL_EQUINOX_PARAM_2150 = 21.8510;

    /**
     * The initial parameter of the approximate expression to calculate autumnal equinox day from 1851 to 1899.
     */
    private const AUTUMNAL_EQUINOX_PARAM_1899 = 22.2588;

    /**
     * The initial parameter of the approximate expression to calculate autumnal equinox day from 1900 to 1979.
     */
    private const AUTUMNAL_EQUINOX_PARAM_1979 = 23.2588;

    /**
     * The initial parameter of the approximate expression to calculate autumnal equinox day from 1980 to 2099.
     */
    private const AUTUMNAL_EQUINOX_PARAM_2099 = 23.2488;

    /**
     * The initial parameter of the approximate expression to calculate autumnal equinox day from 2100 to 2150.
     */
    private const AUTUMNAL_EQUINOX_PARAM_2150 = 24.2488;

    /**
     * Initialize holidays for Japan.
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     */
    public function initialize(): void
    {
        $this->timezone = 'Asia/Tokyo';

        // Add common holidays
        if ($this->year >= 1948) {
            $this->addHoliday($this->newYearsDay($this->year, $this->timezone, $this->locale));
        }

        // Calculate other holidays
        $this->calculateNationalFoundationDay();
        $this->calculateShowaDay();
        $this->calculateConstitutionMemorialDay();
        $this->calculateChildrensDay();
        $this->calculateCultureDay();
        $this->calculateLaborThanksgivingDay();
        $this->calculateEmporersBirthday();
        $this->calculateVernalEquinoxDay();
        $this->calculateComingOfAgeDay();
        $this->calculateGreeneryDay();
        $this->calculateMarineDay();
        $this->calculateMountainDay();
        $this->calculateRespectForTheAgeDay();
        $this->calculateSportsDay();
        $this->calculateAutumnalEquinoxDay();
        $this->calculateSubstituteHolidays();
        $this->calculateCoronationDay();
        $this->calculateEnthronementProclamationCeremony();
        $this->calculateBridgeHolidays();
    }

    /**
     * National Foundation Day. National Foundation Day is held on February 11th and established since 1966.
     *
     * @throws \Exception
     */
    private function calculateNationalFoundationDay(): void
    {
        if ($this->year >= 1966) {
            $this->addHoliday(new Holiday(
                'nationalFoundationDay',
                ['en_US' => 'National Foundation Day', 'ja_JP' => '??????????????????'],
                new DateTime("$this->year-2-11", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Showa Day. Showa Day is held on April 29th and established since 2007.
     *
     * @throws \Exception
     */
    private function calculateShowaDay(): void
    {
        if ($this->year >= 2007) {
            $this->addHoliday(new Holiday(
                'showaDay',
                ['en_US' => 'Showa Day', 'ja_JP' => '????????????'],
                new DateTime("$this->year-4-29", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Constitution Memorial Day. Constitution Memorial Day is held on May 3rd and established since 1948.
     *
     * @throws \Exception
     */
    private function calculateConstitutionMemorialDay(): void
    {
        if ($this->year >= 1948) {
            $this->addHoliday(new Holiday(
                'constitutionMemorialDay',
                ['en_US' => 'Constitution Memorial Day', 'ja_JP' => '???????????????'],
                new DateTime("$this->year-5-3", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }
    /**
     * Children's Day. Children's Day is held on May 5th and established since 1948.
     *
     * @throws \Exception
     */
    private function calculateChildrensDay(): void
    {
        if ($this->year >= 1948) {
            $this->addHoliday(new Holiday(
                'childrensDay',
                ['en_US' => 'Children\'s Day', 'ja_JP' => '???????????????'],
                new DateTime("$this->year-5-5", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Culture Day. Culture Day is held on November 11th and established since 1948.
     *
     * @throws \Exception
     */
    private function calculateCultureDay(): void
    {
        if ($this->year >= 1948) {
            $this->addHoliday(new Holiday(
                'cultureDay',
                ['en_US' => 'Culture Day', 'ja_JP' => '????????????'],
                new DateTime("$this->year-11-3", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Labor Thanksgiving Day. Labor Thanksgiving Day is held on November 23rd and established since 1948.
     *
     * @throws \Exception
     */
    private function calculateLaborThanksgivingDay(): void
    {
        if ($this->year >= 1948) {
            $this->addHoliday(new Holiday(
                'laborThanksgivingDay',
                ['en_US' => 'Labor Thanksgiving Day', 'ja_JP' => '??????????????????'],
                new DateTime("$this->year-11-23", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Emperors Birthday.
     * The Emperors Birthday is on April 29rd and celebrated as such since 1949 to 1988.
     * December 23rd and celebrated as such since 1989 to 2018.
     * February 23rd and celebrated as such since 2020.(Coronation Day of the new Emperor, May 1, 2019)
     *
     * @throws \Exception
     */
    private function calculateEmporersBirthday(): void
    {
        $emporersBirthday = false;
        if ($this->year >=2020) {
            $emporersBirthday = "$this->year-2-23";
        } elseif ($this->year >= 1989 && $this->year <2019) {
            $emporersBirthday = "$this->year-12-23";
        } elseif ($this->year >= 1949 && $this->year <1988) {
            $emporersBirthday = "$this->year-4-29";
        }
        
        if ($emporersBirthday) {
            $this->addHoliday(new Holiday(
                'emperorsBirthday',
                ['en_US' => 'Emperors Birthday', 'ja_JP' => '???????????????'],
                new DateTime($emporersBirthday, new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Coronation Day. Coronation Day is The new Emperor Coronation.
     * This holiday is only 2019.
     *
     * @throws \Exception
     */
    private function calculateCoronationDay(): void
    {
        if (2019 === $this->year) {
            $this->addHoliday(new Holiday(
                'coronationDay',
                ['en_US' => 'Coronation Day', 'ja_JP' => '????????????'],
                new DateTime("$this->year-5-1", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Enthronement Proclamation Ceremony. Enthronement Proclamation Ceremony is The New Emperor enthronement ceremony.
     * This holiday only 2019.
     *
     * @throws \Exception
     */
    private function calculateEnthronementProclamationCeremony(): void
    {
        if (2019 === $this->year) {
            $this->addHoliday(new Holiday(
                'enthronementProclamationCeremony',
                ['en_US' => 'Enthronement Proclamation Ceremony', 'ja_JP' => '?????????????????????'],
                new DateTime("$this->year-10-22", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Calculate Vernal Equinox Day.
     *
     * This national holiday was established in 1948 as a day for the admiration
     * of nature and the love of living things. Prior to 1948, the vernal equinox was an imperial ancestor worship
     * festival called Shunki k??rei-sai (???????????????).
     *
     * @link http://www.h3.dion.ne.jp/~sakatsu/holiday_topic.htm (in Japanese)
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     */
    private function calculateVernalEquinoxDay(): void
    {
        $day = null;
        if ($this->year < 1948 || $this->year > 2150) {
            $day = null;
        } elseif ($this->year >= 1948 && $this->year <= 1979) {
            $day = \floor(self::VERNAL_EQUINOX_PARAM_1979 + self::EQUINOX_GRADIENT * ($this->year - 1980) - \floor(($this->year - 1983) / 4));
        } elseif ($this->year <= 2099) {
            $day = \floor(self::VERNAL_EQUINOX_PARAM_2099 + self::EQUINOX_GRADIENT * ($this->year - 1980) - \floor(($this->year - 1980) / 4));
        } elseif ($this->year <= 2150) {
            $day = \floor(self::VERNAL_EQUINOX_PARAM_2150 + self::EQUINOX_GRADIENT * ($this->year - 1980) - \floor(($this->year - 1980) / 4));
        }

        if (null !== $day) {
            $this->addHoliday(new Holiday(
                'vernalEquinoxDay',
                ['en_US' => 'Vernal Equinox Day', 'ja_JP' => '????????????'],
                new DateTime("$this->year-3-$day", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Calculate Coming of Age Day.
     *
     * Coming of Age Day was established after 1948 on January 15th. After 2000 it was changed to be the second monday
     * of January.
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     * @throws \Exception
     */
    private function calculateComingOfAgeDay(): void
    {
        $date = null;
        if ($this->year >= 2000) {
            $date = new DateTime("second monday of january $this->year", new DateTimeZone($this->timezone));
        } elseif ($this->year >= 1948) {
            $date = new DateTime("$this->year-1-15", new DateTimeZone($this->timezone));
        }

        if (null !== $date) {
            $this->addHoliday(new Holiday(
                'comingOfAgeDay',
                ['en_US' => 'Coming of Age Day', 'ja_JP' => '????????????'],
                $date,
                $this->locale
            ));
        }
    }

    /**
     * Calculates Greenery Day.
     *
     * Greenery Day was established from 1989 on April 29th. After 2007 it was changed to be May 4th.
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     * @throws \Exception
     */
    private function calculateGreeneryDay(): void
    {
        $date = null;
        if ($this->year >= 2007) {
            $date = new DateTime("$this->year-5-4", new DateTimeZone($this->timezone));
        } elseif ($this->year >= 1989) {
            $date = new DateTime("$this->year-4-29", new DateTimeZone($this->timezone));
        }

        if (null !== $date) {
            $this->addHoliday(new Holiday(
                'greeneryDay',
                ['en_US' => 'Greenery Day', 'ja_JP' => '???????????????'],
                $date,
                $this->locale
            ));
        }
    }

    /**
     * Calculates Marine Day.
     *
     * Marine Day was established since 1996 on July 20th. After 2003 it was changed to be the third monday of July.In
     * 2020 is July 23th.
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     */
    private function calculateMarineDay(): void
    {
        $date = null;
        if ($this->year === 2020) {
            $date = new DateTime("$this->year-7-23", new DateTimeZone($this->timezone));
        } elseif ($this->year >= 2003) {
            $date = new DateTime("third monday of july $this->year", new DateTimeZone($this->timezone));
        } elseif ($this->year >= 1996) {
            $date = new DateTime("$this->year-7-20", new DateTimeZone($this->timezone));
        }

        if (null !== $date) {
            $this->addHoliday(new Holiday(
                'marineDay',
                ['en_US' => 'Marine Day', 'ja_JP' => '?????????'],
                $date,
                $this->locale
            ));
        }
    }

    /**
     * Calculates MountainDay
     *
     * Mountain Day. Mountain Day is held on August 11th and established since 2016.In 2020 is August 10th.
     *
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     * @throws \Exception
     */
    private function calculateMountainDay(): void
    {
        $date = null;
        if ($this->year === 2020) {
            $date = new DateTime("$this->year-8-10", new DateTimeZone($this->timezone));
        } elseif ($this->year >= 2016) {
            $date = new DateTime("$this->year-8-11", new DateTimeZone($this->timezone));
        }

        if (null !== $date) {
            $this->addHoliday(new Holiday(
                'mountainDay',
                ['en_US' => 'Mountain Day', 'ja_JP' => '?????????'],
                $date,
                $this->locale
            ));
        }
    }

    /**
     * Calculates Respect for the Age Day.
     *
     * Respect for the Age Day was established since 1996 on September 15th. After 2003 it was changed to be the third
     * monday of September.
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     * @throws \Exception
     */
    private function calculateRespectForTheAgeDay(): void
    {
        $date = null;
        if ($this->year >= 2003) {
            $date = new DateTime("third monday of september $this->year", new DateTimeZone($this->timezone));
        } elseif ($this->year >= 1996) {
            $date = new DateTime("$this->year-9-15", new DateTimeZone($this->timezone));
        }

        if (null !== $date) {
            $this->addHoliday(new Holiday(
                'respectfortheAgedDay',
                ['en_US' => 'Respect for the Aged Day', 'ja_JP' => '????????????'],
                $date,
                $this->locale
            ));
        }
    }

    /**
     * Calculates Health And Sports Day.
     *
     * Health And Sports Day was established since 1966 on October 10th. After 2000 it was changed to be the second
     * monday of October.In 2020 is July 24th.
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     */
    private function calculateSportsDay(): void
    {
        $date = null;
        if ($this->year === 2020) {
            $date = new DateTime("$this->year-7-24", new DateTimeZone($this->timezone));
        } elseif ($this->year >= 2000) {
            $date = new DateTime("second monday of october $this->year", new DateTimeZone($this->timezone));
        } elseif ($this->year >= 1996) {
            $date = new DateTime("$this->year-10-10", new DateTimeZone($this->timezone));
        }

        $holiday_name =['en_US' => 'Health And Sports Day', 'ja_JP' => '????????????'];
        if ($this->year >= 2020) {
            $holiday_name =['en_US' => 'Sports Day', 'ja_JP' => '??????????????????'];
        }

        if (null !== $date) {
            $this->addHoliday(new Holiday(
                'sportsDay',
                $holiday_name,
                $date,
                $this->locale
            ));
        }
    }

    /**
     * Calculate Autumnal Equinox Day.
     *
     * This national holiday was established in 1948 as a day on which to honor
     * one's ancestors and remember the dead. Prior to 1948, the autumnal equinox was an imperial ancestor worship
     * festival called Sh??ki k??rei-sai (???????????????).
     *
     * @link http://www.h3.dion.ne.jp/~sakatsu/holiday_topic.htm (in Japanese)
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     */
    private function calculateAutumnalEquinoxDay(): void
    {
        $day = null;
        if ($this->year < 1948 || $this->year > 2150) {
            $day = null;
        } elseif ($this->year >= 1948 && $this->year <= 1979) {
            $day = \floor(self::AUTUMNAL_EQUINOX_PARAM_1979 + self::EQUINOX_GRADIENT * ($this->year - 1980) - \floor(($this->year - 1983) / 4));
        } elseif ($this->year <= 2099) {
            $day = \floor(self::AUTUMNAL_EQUINOX_PARAM_2099 + self::EQUINOX_GRADIENT * ($this->year - 1980) - \floor(($this->year - 1980) / 4));
        } elseif ($this->year <= 2150) {
            $day = \floor(self::AUTUMNAL_EQUINOX_PARAM_2150 + self::EQUINOX_GRADIENT * ($this->year - 1980) - \floor(($this->year - 1980) / 4));
        }

        if (null !== $day) {
            $this->addHoliday(new Holiday(
                'autumnalEquinoxDay',
                ['en_US' => 'Autumnal Equinox Day', 'ja_JP' => '????????????'],
                new DateTime("$this->year-9-$day", new DateTimeZone($this->timezone)),
                $this->locale
            ));
        }
    }

    /**
     * Calculate the substitute holidays.
     *
     * Generally if a national holiday falls on a Sunday, the holiday is observed the next working day (not being
     * another holiday).
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     */
    private function calculateSubstituteHolidays(): void
    {
        // Get initial list of holiday dates
        $dates = $this->getHolidayDates();

        // Loop through all holidays
        foreach ($this->getHolidays() as $shortName => $date) {
            $substituteDay = clone $date;

            // If holidays falls on a Sunday
            if (0 === (int)$date->format('w')) {
                if ($this->year >= 2007) {
                    // Find next week day (not being another holiday)
                    while (\in_array($substituteDay, $dates, false)) {
                        $substituteDay->add(new DateInterval('P1D'));
                        continue;
                    }
                } elseif ($date >= '1973-04-12') {
                    $substituteDay->add(new DateInterval('P1D'));
                    if (\in_array($substituteDay, $dates, false)) {
                        continue; // @codeCoverageIgnore
                    }
                } else {
                    continue;
                }

                // Add a new holiday that is substituting the original holiday
                $substituteHoliday = new Holiday('substituteHoliday:' . $shortName, [
                    'en_US' => $date->translations['en_US'] . ' Observed',
                    'ja_JP' => '???????????? (' . $date->translations['ja_JP'] . ')',
                ], $substituteDay, $this->locale);

                $this->addHoliday($substituteHoliday);
            }
        }
    }

    /**
     * Calculate public bridge holidays.
     *
     * Any day that falls between two other national holidays also becomes a holiday, known as a bridge holiday.
     *
     * @throws \Yasumi\Exception\InvalidDateException
     * @throws \InvalidArgumentException
     * @throws \Yasumi\Exception\UnknownLocaleException
     * @throws \Exception
     */
    private function calculateBridgeHolidays(): void
    {
        // Get initial list of holidays and iterator
        $datesIterator = $this->getIterator();

        $counter=1;
        // Loop through all defined holidays
        while ($datesIterator->valid()) {
            $previous = $datesIterator->current();
            $datesIterator->next();

            // Skip if next holiday is not set
            if (null === $datesIterator->current()) {
                continue;
            }

            // Determine if gap between holidays is one day and create bridge holiday
            if (2 === (int)$previous->diff($datesIterator->current())->format('%a')) {
                $bridgeDate = clone $previous;
                $bridgeDate->add(new DateInterval('P1D'));

                $this->addHoliday(new Holiday('bridgeDay'.$counter, [
                    'en_US' => 'Bridge Public holiday',
                    'ja_JP' => '???????????????',
                ], $bridgeDate, $this->locale));
                $counter++;
            }
        }
    }
}
