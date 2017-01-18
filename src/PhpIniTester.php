<?php

class PhpIniTester
{
    private $iniFile;

    private $ini;

    private $spec;

    /**
     * PhpIniTester constructor.
     * @param string $iniFile
     * @param string $specFile
     */
    public function __construct($iniFile, $specFile)
    {
        if (!is_file($iniFile)) {
            throw new \InvalidArgumentException(sprintf("No such file: %s", $iniFile));
        }
        if (!is_file($specFile)) {
            throw new \InvalidArgumentException(sprintf("No such file: %s", $specFile));
        }

        $this->iniFile = $iniFile;
        $this->ini = parse_ini_file($iniFile, false, INI_SCANNER_RAW);

        if (false === $this->ini) {
            throw new \RuntimeException(sprintf('Failed to parse ini: %s', $iniFile));
        }

        $spec = file_get_contents($specFile);

        if (false === $spec) {
            throw new \RuntimeException(sprintf('Failed to read spec file: %s', $specFile));
        }

        $this->spec = json_decode($spec, true);
    }

    /**
     * @return array
     */
    public function getResult()
    {
        $result = [];

        foreach ($this->spec as $setting => $expected) {
            if (!isset($this->ini[$setting])) {
                $result[] = sprintf("No such setting: %s", $setting);
                continue;
            }

            $actual = $this->ini[$setting];

            if ($this->normalize($actual) !== $this->normalize($expected)) {
                $result[] = sprintf("%s is expected to set '%s'. actual: '%s'", $setting, $expected, $actual);
            }
        }

        return $result;
    }

    /**
     * @param string $value
     * @return int
     */
    private function normalize($value)
    {
        if (strtolower($value) === 'off' || $value === '' || $value === 0 || $value === '0' || $value === false) {
            return 0;
        } elseif (strtolower($value) === 'on' || $value === 1 || $value === '1' || $value === true) {
            return 1;
        } else {
            return $value;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result = $this->getResult();

        if (count($result) > 0) {
            array_unshift($result, sprintf('Inspection on %s', $this->iniFile));

            return implode("\n", $result);
        } else {
            return "All specs are satisfied.\n";
        }
    }
}
