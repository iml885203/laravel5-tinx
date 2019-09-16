<?php

namespace iml885203\Laravel5Tinx\Includes;

use Illuminate\Support\Str;

class IncludeManager
{
    /**
     * @param array $names
     * @return void
     * */
    public function generateIncludesFile($names)
    {
        $config = config('tinx');

        $contents = view('tinx::includes', compact('names', 'config'))->render();

        $contents = preg_replace('/^<php/', '<?php', $contents);

        app('tinx.storage')->put('includes.php', $contents);
    }
}
