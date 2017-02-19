<?php

class Roi{

          
        public function __construct($data){
            
                    $this->inputArr=$data;
                    
                    //get the amortization data
                    $this->amortSchedArr=Amortization::$amortSched;
                    $this->payment=Amortization::$payment;
                    
                    //divide percentages by 100, negate expenses, convert monthly data into annual
                    $this->prepare();
                    $this->calculate();


                    //get constants, annual appreciation, initial rents, mortgage pay, property address, cash outlay, years to sale
                    //calculate annual values: gross rent,insurance,taxes,mortgage payments, managment fees,vac n collection losses fees, cap improv fees
                    //repeat for each year
                        //calculate cash flow
                        //calculate equity
                        //appreciate values
                    //calculate roi

                    //output format
        }//end __construct
        
        
        
        private function prepare(){
            
            
                /*  print_r($this->inputArr);
                 *   [prprtyAddr] => 203 Poplar St
                    [fxPrchPrcDlrAmt] => 387000
                    [fxdDwnPmtPrct] => 0.15
                    [termYrs] => 30
                    [APR] => 4.1
                    [strtMnthlyRent] => 2100
                    [mnthlyMngmtPrct] => 0.04
                    [mnthlyMntnNCpImprPrct] => 0.06
                    [mnthlyVacColLossPrct] => 0.03
                    [fxdClsngCstDlrAmt] => 9000
                    [annlInsDlrAmt] => 420
                    [annlTxsDlrAmt] => 4654
                    [annlApprcPrct] => 0.055
                    [yrsToSale] => 6
                )
                 */
                
                    //divide pertcentage variables by 100,copy everything into prepped array
                    foreach($this->inputArr as $key => $value){
                                if (strpos($key, 'Prct') !== false) {
                                    $this->preppedData[$key] = $value/100; //convert percentage values to floats
                                }else{
                                    $this->preppedData[$key] = $value;//otherwise straight copy
                                }
                    }
                    
                    //convert monthly values into annual values
                    $this->preppedData["strtMnthlyRent"]*=12;
                    $this->payment*=12;
                    
                    //convert expenses into negatives
                    $negArrKeys=array("fxdDwnPmtPrct","mnthlyMngmtPrct","mnthlyMntnNCpImprPrct",
                        "mnthlyVacColLossPrct","fxdClsngCstDlrAmt","annlInsDlrAmt","annlTxsDlrAmt",);
                    foreach($negArrKeys as $value){
                                    $this->preppedData[$value] *= -1; //convert percentage values to floats
                    }
                    $this->payment*=-1;
                    
                    //calculate cash required at closing
                    $this->preppedData["cashAtClose"]=($this->preppedData["fxdDwnPmtPrct"]
                                                        *$this->preppedData["fxPrchPrcDlrAmt"]
                                                        +$this->preppedData["fxdClsngCstDlrAmt"]);
                    
        }//end prepare
        
        
        
        private function addAppreciation(){
                
                //these are base costs and valuations that go up based on tearly appreciation
                $asscIndex=array("fxPrchPrcDlrAmt","strtMnthlyRent","annlInsDlrAmt","annlTxsDlrAmt");

                //these are yearly costs that go up based on increased gross rents
                $appreciablePerctRentInputs=array("mnthlyMngmtPrct","mnthlyMntnNCpImprPrct","mnthlyVacColLossPrct");
                
                //'asscIndex' type loop
                foreach ($asscIndex as $key => $asscIndex) {
                        
                        //get the initial value of the appreciable list
                        $thisVal=round($this->preppedData[$asscIndex],2);
                        
                        
                        if($asscIndex=="fxPrchPrcDlrAmt"){
                            
                                //save the starting value of the property
                                $arrVals[$asscIndex][0]=$thisVal;

                                //only the house appreciates in the first year
                                $thisVal+=round($thisVal*$this->preppedData["annlApprcPrct"],2);
                        }
                        
                        //there are no expense at beginning year 0
                        else {
                                $arrVals[$asscIndex][0]=0;
                        }

                        //loop through the remaining years beginning w year 1
                        foreach (range(1,$this->preppedData["yrsToSale"]) as $year) {
                            
                                //record the current value of the 'asscIndex' type values
                                $arrVals[$asscIndex][$year]=$thisVal;

                                //appreciate the value at the end of the year to record in the next year
                                //increase based on input annual appreciation %
                                $thisVal+=round($thisVal*$this->preppedData["annlApprcPrct"],2);
                        }                                                
                        
                        //store the 'asscIndex' type value in array
                        $retArr[$asscIndex]=$arrVals[$asscIndex];
                        
                }// end foreach 'asscIndex' type loop

                //'appreciablePerctRentInputs' type loop
                foreach ($appreciablePerctRentInputs as $key2 => $asscIndex2) {
                    
                        //set year 0 = 0
                        $arrVals[$asscIndex2][0]=0;

                        //loop through the remaining years beginning w year 1
                        foreach (range(1,$this->preppedData["yrsToSale"]) as $year) {
                            
                                //here the fixed pertange costs increase based on increased rents
                                $arrVals[$asscIndex2][$year]=round($retArr["strtMnthlyRent"][$year]*$this->preppedData[$asscIndex2],2);
                        }
                        
                        //store the '$asscIndex2' type value in array
                        $retArr[$asscIndex2]=$arrVals[$asscIndex2];
                    }// end foreach 
                    
                //calculate cashAtClose array separately
                foreach (range(0,$this->preppedData["yrsToSale"]) as $year) 
                        
                        if($year==0){
                            $retArr["cashAtClose"][$year]=$this->preppedData["cashAtClose"];
                        }else{
                            $retArr["cashAtClose"][$year]=0;
                        }
        
        return $retArr;
            
        }//end apprectiate
        
        
        
        private function sumCols(){
                
                $colNamesToSum=array("strtMnthlyRent","annlInsDlrAmt","annlTxsDlrAmt",
                        "mnthlyMngmtPrct","mnthlyMntnNCpImprPrct","mnthlyVacColLossPrct","cashAtClose");
                foreach ($colNamesToSum as $asscIndex) {
                        $sum=0;
                        foreach (range(0,$this->inputArr["yrsToSale"]) as $year) {
                            $sum+=$this->appreciatedValsArr[$asscIndex][$year];
                        }
                        //monthlt cols multiplied by 12
                        if (strpos(strtolower($asscIndex), 'mnthly') !== false) {
                            $this->appreciatedValsArr[$asscIndex]["sum"]=$sum;
                        }
                        //assign
                        $this->appreciatedValsArr[$asscIndex]["sum"]=$sum;
                }
        }//end sumCols
        
        
        
        private function sumRows(){
                     
                    $colNamesToSum=array("strtMnthlyRent","annlInsDlrAmt","annlTxsDlrAmt",
                        "mnthlyMngmtPrct","mnthlyMntnNCpImprPrct","mnthlyVacColLossPrct","cashAtClose");
                    
                    $this->sumRow=array();
                    
                    foreach (range(0,$this->inputArr["yrsToSale"]) as $year) {
                        
                            foreach ($colNamesToSum as $asscIndex) {
                                        @$this->sumRow[$year]+=$this->appreciatedValsArr[$asscIndex][$year];
                            }
                            if($year!=0){
                                $this->sumRow[$year]+=$this->payment;
                            }
                    }
        }//end sumRows
        
        
        
        private function calRoi(){
            
//                    ROI = ( (Earnings) - Initial Invested Amount) / Initial Invested Amount) ) × 100
                    $initInvest=(-1)*$this->appreciatedValsArr["cashAtClose"][0];
                    
                    //no earnings at start year 0 just investment
                    $thisRoi[0]=round(((0-$initInvest)/$initInvest)*100,2);
                    
                    //calculate for each following year
                    foreach (range(1,$this->inputArr["yrsToSale"]) as $year) {
                            $thisRoi[$year]=round((($this->sumRow[$year]-$initInvest)/$initInvest)*100,2);
                    }
                    $this->roi=$thisRoi;
        }//end calRoi
        
        
        
        private function calEquity(){
                
                    foreach (range(0,$this->inputArr["yrsToSale"]) as $year) {
                        
                        //if year 0 assume equity as what was paid for house
                        if($year==0){//fxPrchPrcDlrAmt $this->preppedData["fxPrchPrcDlrAmt"] $this->preppedData["fxdDwnPmtPrct"]
                            $arr[$year]=$this->preppedData["fxPrchPrcDlrAmt"]*(-1)*$this->preppedData["fxdDwnPmtPrct"];echo "<br />";
                        }else{
                        //otherwise equity is appreciated value of property - balance due on mortgage
                            $arr[$year]=$this->appreciatedValsArr["fxPrchPrcDlrAmt"][$year]-$this->amortSchedArr[$year*12-1]["balance"];echo "<br />";
                        }
                    }
                    
                    $this->equity=$arr;
        }// end calEquity
        
        
        
        private function prepOutput(){

        }
        
        
        
        private function calculate(){
        
                    //this compounds the above appreciation properties for each year
                    $this->appreciatedValsArr=$this->addAppreciation();

                    //calculate equity in property for each row year 
                    $this->calEquity();
                    
                    //total the appreciation cols, returns $this->appreciatedValsArr
                    $this->sumCols();
                    
                    echo "<pre>";
                    print_r($this->appreciatedValsArr);
                    echo "</pre>";

                    //sum each year (year) return $this->sumRow
                    $this->sumRows();
                    
                    
                    //ROI = ( (Earnings) - Initial Invested Amount) / Initial Invested Amount) ) × 100
                    $this->calRoi();
                    
                    //modify the output to fit the previous version's format
                    //$this->prepOutput();

            
        }//end calculate
          
        
    }//end roi class

