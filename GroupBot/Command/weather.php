<?php
/**
 * Created by PhpStorm.
 * User: Alexander
 * Date: 7/11/2015
 * Time: 3:07 AM
 */
namespace GroupBot\Command;

use GroupBot\Libraries\Carbon;
use GroupBot\Telegram;
use GroupBot\Types\Command;

class weather extends Command
{
    public function main()
    {
        $realtime = \GroupBot\Brains\Weather\Weather::realtime();
        $uv = \GroupBot\Brains\Weather\Weather::uv_index();
        $sunrise = \GroupBot\Brains\Weather\Weather::sunrise();
        $forecast = \GroupBot\Brains\Weather\Weather::forecast();

        $today = $forecast[Carbon::today()->format('l')];
        $tomorrow = $forecast[Carbon::tomorrow()->format('l')];

        $today_icon = \GroupBot\Brains\Weather\Weather::$icon_map[$today['forecast_icon_code']];
        $tomorrow_icon = \GroupBot\Brains\Weather\Weather::$icon_map[$tomorrow['forecast_icon_code']];

        $out = emoji(0x1F321) . " Weather for *" . $realtime->name . "*, *" . $realtime->state . "*."
            . "\n"
            . "\n" . emoji($today_icon) . " *Now:*"
            . "\n`   `• It's currently *" . $realtime->air_temp . "°C*"
            . "\n`   `• The UV index is at *" . $uv->value . "* ([" . $uv->description . "](http://www.arpansa.gov.au/uvindex/realtime/images//per_rt.gif))"
            . "\n"
            . "\n" . emoji($today_icon) . " *Today:*"
            . "\n`   `• " . $today['forecast']
            . "\n`   `• Maximum of *" . $today['air_temperature_maximum'] . "°C*"
            . "\n`   `• *" . $today['probability_of_precipitation'] . "* chance of rain"
            . "\n`   `• Sunrise is at *$sunrise->sunrise* today, and sunset at *$sunrise->sunset*"
            . "\n"
            . "\n" . emoji($tomorrow_icon) . " *Tomorrow:*"
            . "\n`   `• " . $tomorrow['forecast']
            . "\n`   `• Minimum of *" . $tomorrow['air_temperature_minimum'] . "°C*, maximum of *" . $tomorrow['air_temperature_maximum'] . "°C*"
            . "\n`   `• *" . $tomorrow['probability_of_precipitation'] . "* chance of rain";


        Telegram::talk($this->Message->Chat->id, $out, true);
    }
}