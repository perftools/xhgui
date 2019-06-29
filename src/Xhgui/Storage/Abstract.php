<?php
/**
 * Abstract class for all storage drivers.
 *
 * CAUTION: please use interface as a typehint!
 */
class Xhgui_Storage_Abstract
{
    /**
     * Try to get date from Y-m-d H:i:s or from timestamp
     *
     * @param string|int $date
     * @param string $direction
     * @return \DateTime
     */
    protected function getDateTimeFromString($date, $direction = 'start')
    {
        try {
            $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
            if (!empty($datetime) && $datetime instanceof \DateTime) {
                return $datetime;
            }
        } catch (\Exception $e) {
        }

        // try without time
        try {
            $datetime = \DateTime::createFromFormat('Y-m-d', $date);
            if (!empty($datetime) && $datetime instanceof \DateTime) {

                if ($direction === 'start') {
                    $datetime->setTime(0, 0, 0);
                } elseif ($direction === 'end') {
                    $datetime->setTime(23, 59, 59);
                }

                return $datetime;
            }
        } catch (\Exception $e) {
        }

        // try using timestamp
        try {
            $datetime = \DateTime::createFromFormat('U', $date);
            if (!empty($datetime) && $datetime instanceof \DateTime) {
                return $datetime;
            }
        } catch (\Exception $e) {
        }

        throw new \InvalidArgumentException('Unable to parse date');
    }
}
