<?php


namespace App\Http\Traits;


use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait AnnualSmugmugSetup
{
    public static $HOUSE_PHOTOS = "House Photos";

    /**
     * @param string      $house
     * @param string|null $endpoint
     *
     * @return mixed
     */
    public function getHouseFolder(string $house, string $endpoint = null)
    {
        try {
            return $this->client->get("folder/user/" . $this->username . "/" . $this->getThisYear() . "/" . self::customSlugger(self::$HOUSE_PHOTOS) . "/" . $house . $endpoint);
        } catch (ClientException $exception) {
            if ($exception->getCode() === 404) {
                $this->createHouseFolders();
                return $this->getHouseFolder($house, $endpoint);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * Smugmug is divided into folders by year: eg 2019-2020 (for the Academic year of 2019).
     * This function will get the right folder for this year, based on the current month.
     *
     * @return string
     */
    private function getThisYear(): string
    {
        $now = now();
        // Essentially, if it's August or later in the year, then we get "This year - Next Year"
        if ($now->month >= Carbon::AUGUST) {
            $year = sprintf("%s-%s", $now->year, $now->year + 1);
        } else {
            // If it's before August, then our academic year is technically "Last Year - This Year"
            $year = sprintf("%s-%s", $now->year - 1, $now->year);
        }

        return $year;
    }

    /**
     * @param string $input
     *
     * @return string
     */
    public static function customSlugger(string $input): string
    {
        return implode("-",
            array_map(
                'ucfirst',
                explode(
                    "-",
                    Str::slug($input)
                )
            )
        );
    }

    /**
     * @return array
     */
    private function createHouseFolders()
    {
        $folders = config('cranleigh.houses');
        $smugFolders = [];
        $housePhotosFolder = $this->getThisYearsHousePhotosFolder();
        foreach ($folders as $house) {
            try {
                $smugFolders[] = $this->client->post($housePhotosFolder->Uri . "!folders", [
                    "UrlName" => self::customSlugger($house),
                    "Name" => $house,
                    "Privacy" => 'Unlisted',
                ]);
            } catch (ClientException $exception) {
                $body = $exception->getRequest()->getBody();
                $body->rewind();

                Log::error($exception->getMessage(), [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'body' => $body->getContents(),
                ]);
            }
        }
        return $smugFolders;
    }

    /**
     * @return string
     */
    public function getThisYearsHousePhotosFolder()
    {
        try {
            return $this->client->get($this->getThisYearsFolder()->Uri . "/House-Photos");

        } catch (ClientException $exception) {
            if ($exception->getCode() === 404) {
                // Create the top level folders
                $this->createTopLevelFolders();
                // Try this method again...
                return call_user_func([$this, __METHOD__]);
            } else {
                throw $exception;
            }
        }

    }

    /**
     * @return mixed
     */
    public function getThisYearsFolder()
    {
        try {
            return $this->client->get("folder/user/" . $this->username . "/" . $this->getThisYear());
        } catch (ClientException $exception) {
            if ($exception->getCode() === 404) {
                return $this->client->post("folder/user/" . $this->username . "/!folders", [
                    "UrlName" => $this->getThisYear(),
                    "Name" => $this->getThisYear(),
                    "Privacy" => "Public",
                ]);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @return array
     */
    private function createTopLevelFolders()
    {
        $folders = [
            "Misc" => "Public",
            "Music" => "Public",
            "Sport" => "Public",
            "Drama" => "Public",
            "Trips" => "Public",
            self::$HOUSE_PHOTOS => "Public",
        ];

        $smugFolders = [];
        foreach ($folders as $name => $privacy) {
            try {
                $smugFolders = $this->client->post($this->getThisYearsFolder()->Uri . "!folders", [
                    "UrlName" => self::customSlugger($name),
                    "Name" => $name,
                    "Privacy" => $privacy,
                ]);
            } catch (ClientException $exception) {
                $body = $exception->getRequest()->getBody();
                $body->rewind();
                Log::error($exception->getMessage(),
                    [
                        'code' => $exception->getCode(),
                        'body' => $body->getContents(),
                    ]
                );
            }
        }
        return $smugFolders;
    }
}
