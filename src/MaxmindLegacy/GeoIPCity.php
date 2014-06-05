<?php

namespace MaxmindLegacy;
use MaxmindLegacy\GeoIPDnsRecord;
use MaxmindLegacy\GeoIPRecord;
use MaxmindLegacy\GeoIP;

/**
 * Description of GeoIPCity
 *
 * @author kubrey
 */
class GeoIPCity {
    
    const FULL_RECORD_LENGTH = 50;
    
    private $geoIp;
    
    /**
     * 
     * @param GeoIP $gi
     */
    public function __construct($gi) {
        $this->geoIp =  $gi;
    }

    public function getrecordwithdnsservice($str) {
        $record = new GeoIPDnsRecord();
        $keyvalue = explode(";", $str);
        foreach ($keyvalue as $keyvalue2) {
            list($key, $value) = explode("=", $keyvalue2);
            if ($key == "co") {
                $record->country_code = $value;
            }
            if ($key == "ci") {
                $record->city = $value;
            }
            if ($key == "re") {
                $record->region = $value;
            }
            if ($key == "ac") {
                $record->areacode = $value;
            }
            if ($key == "dm" || $key == "me") {
                $record->dmacode = $value;
                $record->metrocode = $value;
            }
            if ($key == "is") {
                $record->isp = $value;
            }
            if ($key == "or") {
                $record->org = $value;
            }
            if ($key == "zi") {
                $record->postal_code = $value;
            }
            if ($key == "la") {
                $record->latitude = $value;
            }
            if ($key == "lo") {
                $record->longitude = $value;
            }
        }
        $number = $GLOBALS['GEOIP_COUNTRY_CODE_TO_NUMBER'][$record->country_code];
        $record->country_code3 = $GLOBALS['GEOIP_COUNTRY_CODES3'][$number];
        $record->country_name = $GLOBALS['GEOIP_COUNTRY_NAMES'][$number];
        if ($record->region != "") {
            if (($record->country_code == "US") || ($record->country_code == "CA")) {
                $record->regionname = $GLOBALS['ISO'][$record->country_code][$record->region];
            } else {
                $record->regionname = $GLOBALS['FIPS'][$record->country_code][$record->region];
            }
        }
        return $record;
    }

    public function _get_record_v6($ipnum) {
        $seek_country = $this->geoIp->_geoip_seek_country_v6($ipnum);
        if ($seek_country == $this->geoIp->databaseSegments) {
            return null;
        }
        return $this->_common_get_record($seek_country);
    }

    public function _common_get_record($seek_country) {
        // workaround php's broken substr, strpos, etc handling with
        // mbstring.func_overload and mbstring.internal_encoding
        $mbExists = extension_loaded('mbstring');
        if ($mbExists) {
            $enc = mb_internal_encoding();
            mb_internal_encoding('ISO-8859-1');
        }

        $record_pointer = $seek_country + (2 * $this->geoIp->record_length - 1) * $this->geoIp->databaseSegments;

        if ($this->geoIp->flags & GeoIP::GEOIP_MEMORY_CACHE) {
            $record_buf = substr($this->geoIp->memory_buffer, $record_pointer, self::FULL_RECORD_LENGTH);
        } elseif ($this->geoIp->flags & GeoIP::GEOIP_SHARED_MEMORY) {
            $record_buf = @shmop_read($this->geoIp->shmid, $record_pointer, self::FULL_RECORD_LENGTH);
        } else {
            fseek($this->geoIp->filehandle, $record_pointer, SEEK_SET);
            $record_buf = fread($this->geoIp->filehandle, self::FULL_RECORD_LENGTH);
        }
        $record = new GeoIPRecord();
        $record_buf_pos = 0;
        $char = ord(substr($record_buf, $record_buf_pos, 1));
        $record->country_code = $this->geoIp->GEOIP_COUNTRY_CODES[$char];
        $record->country_code3 = $this->geoIp->GEOIP_COUNTRY_CODES3[$char];
        $record->country_name = $this->geoIp->GEOIP_COUNTRY_NAMES[$char];
        $record->continent_code = $this->geoIp->GEOIP_CONTINENT_CODES[$char];
        $record_buf_pos++;
        $str_length = 0;

        // Get region
        $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        while ($char != 0) {
            $str_length++;
            $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        }
        if ($str_length > 0) {
            $record->region = substr($record_buf, $record_buf_pos, $str_length);
        }
        $record_buf_pos += $str_length + 1;
        $str_length = 0;
        // Get city
        $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        while ($char != 0) {
            $str_length++;
            $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        }
        if ($str_length > 0) {
            $record->city = substr($record_buf, $record_buf_pos, $str_length);
        }
        $record_buf_pos += $str_length + 1;
        $str_length = 0;
        // Get postal code
        $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        while ($char != 0) {
            $str_length++;
            $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        }
        if ($str_length > 0) {
            $record->postal_code = substr($record_buf, $record_buf_pos, $str_length);
        }
        $record_buf_pos += $str_length + 1;
        $str_length = 0;
        // Get latitude and longitude
        $latitude = 0;
        $longitude = 0;
        for ($j = 0; $j < 3; ++$j) {
            $char = ord(substr($record_buf, $record_buf_pos++, 1));
            $latitude += ($char << ($j * 8));
        }
        $record->latitude = ($latitude / 10000) - 180;
        for ($j = 0; $j < 3; ++$j) {
            $char = ord(substr($record_buf, $record_buf_pos++, 1));
            $longitude += ($char << ($j * 8));
        }
        $record->longitude = ($longitude / 10000) - 180;
        if (GeoIP::GEOIP_CITY_EDITION_REV1 == $this->geoIp->databaseType) {
            $metroarea_combo = 0;
            if ($record->country_code == "US") {
                for ($j = 0; $j < 3; ++$j) {
                    $char = ord(substr($record_buf, $record_buf_pos++, 1));
                    $metroarea_combo += ($char << ($j * 8));
                }
                $record->metro_code = $record->dma_code = floor($metroarea_combo / 1000);
                $record->area_code = $metroarea_combo % 1000;
            }
        }
        if ($mbExists) {
            mb_internal_encoding($enc);
        }
        return $record;
    }

    public function GeoIP_record_by_addr_v6($addr) {
        if ($addr == null) {
            return 0;
        }
        $ipnum = inet_pton($addr);
        return $this->_get_record_v6($ipnum);
    }

    public function _get_record($ipnum) {
        $seek_country = $this->geoIp->_geoip_seek_country($ipnum);
        if ($seek_country == $this->geoIp->databaseSegments) {
            return null;
        }
        return $this->_common_get_record($seek_country);
    }

    public function GeoIP_record_by_addr($addr) {
        if ($addr == null) {
            return 0;
        }
        $ipnum = ip2long($addr);
        return $this->_get_record($ipnum);
    }

}
