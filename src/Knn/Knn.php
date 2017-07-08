<?php

namespace KNN\Knn;
/**
 * Created by PhpStorm.
 * User: samirsouza<samir.guitar@gmail.com>
 * Date: 19/06/17
 * Time: 22:43
 */
class Knn
{
  public $url;
  public $trainingSet;
  public $testSet;
  public $splitNumber;
  public $classificationTable;
  public $generalClassificationTable;

  private $decimalNumberVars;

  /**
   * @return int
   */
  public function getDecimalNumberVars()
  {
    return $this->decimalNumberVars;
  }

  public function __construct($url = null, $splitNumber = null, $length = 0)
  {
    $this->url = $url;
    $this->splitNumber = $splitNumber;
    $this->trainingSet = [];
    $this->testSet = [];
    $this->decimalNumberVars = 3;
    // number of parameters
    $this->length = $length;

    $this->classificationTable = [
      "Iris-setosa" => [
        "tp" => 0,
        "tn" => 0,
        "fp" => 0,
        "fn" => 0,
        "precision" => 0,
        "recall" => 0,
        "specificity" => 0,
        "fMeasure" => 0,
        "accuracy" => 0
      ],
      "Iris-versicolor" => [
        "tp" => 0,
        "tn" => 0,
        "fp" => 0,
        "fn" => 0,
        "precision" => 0,
        "recall" => 0,
        "specificity" => 0,
        "fMeasure" => 0,
        "accuracy" => 0
      ],
      "Iris-virginica" => [
        "tp" => 0,
        "tn" => 0,
        "fp" => 0,
        "fn" => 0,
        "precision" => 0,
        "recall" => 0,
        "specificity" => 0,
        "fMeasure" => 0,
        "accuracy" => 0
      ]
    ];

    $this->generalClassificationTable = [
      "tp" => 0,
      "tn" => 0,
      "fp" => 0,
      "fn" => 0,
      "precision" => 0,
      "recall" => 0,
      "specificity" => 0,
      "fMeasure" => 0,
      "accuracy" => 0
    ];
  }

  /**
   * Handle - Preparo dos dados
   * @param null $url
   * @param null $splitNumber
   * @return string
   */
  public function loadDataset($url = null, $splitNumber = null)
  {
    if ($url == null)
      $url = $this->url;

    if ($splitNumber == null)
      $splitNumber = $this->splitNumber;

    try {
      $content = file_get_contents($url);

      if ($content === false) {
        return "Wrong data!";
      }
    } catch (Exception $e) {
      return "Problem: " . $e;
    }

    $content = preg_split("/[\n,]+/", $content, -1, PREG_SPLIT_NO_EMPTY);
    $total = count($content);
//    for ($j = 0, $i = 0; $i < $total; $j++, $i += 5) {
    for ($i = 0; $i < $total; $i++) {
      $arrayData[] = $content[$i];

      if (($i + 1) % ($this->length + 1) == 0) {
        if (rand(1, 100) < $splitNumber * 100) {
          $this->trainingSet[] = $arrayData;
        } else {
          $this->testSet[] = $arrayData;
        }
        $arrayData = null;
      }
    }
  }

  /**
   * Similarity - cálculo da distância entre duas instâncias
   */
  public function euclideanDistance($instance1, $instance2, $length)
  {
    $distance = 0;
    for ($i = 0; $i < $length; $i++) {
      $distance += pow(intval($instance1[$i] - $instance2[$i]), 2);
    }
    return sqrt($distance);
  }

  /**
   * Neighbors: localiza k instâncias de dados mais semelhantes
   */
//  public function getNeighbors($trainigSet, $testInstance, $k)
  public function getNeighbors($testElem, $k)
  {
    $distances = [];
//    $length = count($this->testSet)-1;
    $length = $this->length;
    $lengthTraining = count($this->trainingSet);
    for ($i = 0; $i < $lengthTraining; $i++) {
      $dist = $this->euclideanDistance($this->testSet[$testElem], $this->trainingSet[$i], $length);
      $distances[] = [$this->trainingSet[$i], $dist];
    }

    usort($distances, function ($a, $b) {
      return $a[1] - $b[1];
    });

    $neighbors = [];
    for ($i = 0; $i < $k; $i++) {
      $neighbors[] = $distances[$i][0];
    }
    return $neighbors;
  }

  /**
   * Response: gera uma resposta a partir de um conjunto de instância de dados
   */
  public function getResponse($neighbors)
  {
    $classVotes = [];
    $lengthNeighbors = count($neighbors);

    for ($i = 0; $i < $lengthNeighbors; $i++) {
      $response = strval(end($neighbors[$i]));
      if (array_key_exists($response, $classVotes)) {
        $classVotes[$response] += 1;
      } else {
        $classVotes[$response] = 1;
      }
    }
    arsort($classVotes);
    reset($classVotes);
    return key($classVotes);
  }

  /**
   * Accuracy: calcula a accuracy da predição (classificação)
   */
  public function getAccuracy($predictions)
  {
    $correct = 0;
    $lengthTestSet = count($this->testSet);
    for ($i = 0; $i < $lengthTestSet; $i++) {
      $strToPrint = '';
      if (end($this->testSet[$i]) == $predictions[$i]) {
        $correct++;
      } else {
        $strToPrint = " <<<<<<< DIFFERENCE";
      }

      if (DEBUG) {
        print "\n->>>Test: " . end($this->testSet[$i]);
        print "\t\tPrediction: " . $predictions[$i] . $strToPrint;
      }
    }
    return ($correct / doubleval($lengthTestSet)) * 100;
  }

  public function buildIndividualConfusionMatrix($actualClass, $result, $count)
  {
    if ($actualClass == $result) {
      $this->classificationTable[strval(end($this->testSet[$count]))]['tp']++;

      foreach ($this->classificationTable as $k => $v) {
        if ($k != $actualClass)
          $this->classificationTable[$k]['tn']++;
      }
    } else {
      $this->classificationTable[strval(end($this->testSet[$count]))]['fn']++;
      $this->classificationTable[strval($result)]['fp']++;
    }
  }

  public function buildGeneralConfusionMatrix()
  {
    foreach ($this->classificationTable as $k => $v) {
      $this->generalClassificationTable["tp"] += $v["tp"];
      $this->generalClassificationTable["tn"] += $v["tn"];
      $this->generalClassificationTable["fp"] += $v["fp"];
      $this->generalClassificationTable["fn"] += $v["tp"];
    }
  }

  public function fillIndividualConfusionMatrix()
  {
    foreach ($this->classificationTable as $k => $v) {
      $this->classificationTable[$k]["accuracy"] = floatval($this->getAccuracyFinal($v["tp"], $v["tn"], $v["fp"], $v["fn"]));
      $this->classificationTable[$k]["precision"] = floatval($this->getPrecisionFinal($v["tp"], $v["fp"]));
      $this->classificationTable[$k]["recall"] = floatval($this->getSensibilityRecallFinal($v["tp"], $v["fn"]));
      $this->classificationTable[$k]["specificity"] = floatval($this->getSpecificityFinal($v["tn"], $v["fp"]));
      $this->classificationTable[$k]["fMeasure"] = $this->getFMeasureFinal($this->classificationTable[$k]["precision"], $this->classificationTable[$k]["recall"]);
    }
  }

  public function fillGeneralConfusionMatrix()
  {
    $this->generalClassificationTable["accuracy"] = floatval($this->getAccuracyFinal($this->generalClassificationTable["tp"], $this->generalClassificationTable["tn"], $this->generalClassificationTable["fp"], $this->generalClassificationTable["fn"]));
    $this->generalClassificationTable["precision"] = floatval($this->getPrecisionFinal($this->generalClassificationTable["tp"], $this->generalClassificationTable["fp"]));
    $this->generalClassificationTable["recall"] = floatval($this->getSensibilityRecallFinal($this->generalClassificationTable["tp"], $this->generalClassificationTable["fn"]));
    $this->generalClassificationTable["specificity"] = floatval($this->getSpecificityFinal($this->generalClassificationTable["tn"], $this->generalClassificationTable["fp"]));
    $this->generalClassificationTable["fMeasure"] = $this->getFMeasureFinal($this->generalClassificationTable["precision"], $this->generalClassificationTable["recall"]);
  }

  private function getAccuracyFinal($TP, $TN, $FP, $FN)
  {
    //Acurácia       = TP+TN/(TP+TN+FP+FN) = (120+310)/(120+310+40+30) = .86
    $result = ($TP + $TN) / ($TP + $TN + $FP + $FN);
    return number_format($result, $this->decimalNumberVars);
  }

  private function getSensibilityRecallFinal($TP, $FN)
  {
    //    Sensibilidade  = TP/(TP + FN) = 120 / (120+30) = .8
    $result = $TP / ($TP + $FN);
    return number_format($result, $this->decimalNumberVars);
  }

  private function getPrecisionFinal($TP, $FP)
  {
    //    Precisão       = TP/(TP + FP) = 120 / (120+40) = .75
    $result = $TP / ($TP + $FP);
    return number_format($result, $this->decimalNumberVars);
  }

  private function getSpecificityFinal($TN, $FP)
  {
    //Especificidade = TN/(TN + FP) = 310 / (310+40) = .88
    $result = $TN / ($TN + $FP);
    return number_format($result, $this->decimalNumberVars);
  }

  private function getFMeasureFinal($Precision, $Recall)
  {
    //Medida - F     = 2 *(Precisão X Recall) / (Precisão+Recall)
    //      = 2 * .75 * .8 / (.75 + .8) = .77
    $result = ((2 * ($Precision * $Recall)) / ($Precision + $Recall));
    return number_format($result, $this->decimalNumberVars);
  }

//  public function confusionMatrix(){
//    $precision = '';    // precisão
//    $recall = '';       // sensibilidade
//    $specificity = '';  // especificidade
//    $accuracy = '';     // acurácia
//    $fMeasure= '';      // medida-F
//  }
}