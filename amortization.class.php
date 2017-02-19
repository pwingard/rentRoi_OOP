<?php
class Amortization {
        private $loan_amount;
        private $term_years;
        private $interest;
        private $terms;
        private $period;
        private $currency;
        private $principal;
        private $balance;
        private $term_pay;
        public static $amortSched; 
        public static $payment; 
        
        public function __construct($data){
                if($this->validate($data)) {
                        $this->loan_amount 	= (float) $data['loan_amount'];
                        $this->term_years 	= (int) $data['term_years'];
                        $this->interest 	= (float) $data['interest'];
                        $this->terms 		= (int) $data['terms'];
                        $this->data             = $data;

                        $this->terms = ($this->terms == 0) ? 1 : $this->terms;
                        $this->period = $this->terms * $this->term_years;
                        $this->interest = ($this->interest/100) / $this->terms;
                }
        }

        private function validate($data) {
                $data_format = array(
                        'loan_amount' 	=> 0,
                        'term_years' 	=> 0,
                        'interest' 	=> 0,
                        'terms' 	=> 0
                        );
                $validate_data = array_diff_key($data_format,$data);

                if(empty($validate_data)) {
                        return true;
                }else{
                        echo "<div style='background-color:#ccc;padding:0.5em;'>";
                        echo '<p style="color:red;margin:0.5em 0em;font-weight:bold;background-color:#fff;padding:0.2em;">Missing Values</p>';
                        foreach ($validate_data as $key => $value) {
                                echo ":: Value <b>$key</b> is missing.<br>";
                        }
                        echo "</div>";
                        return false;
                }
        }
        private function calculate($i){
                $deno = 1 - 1 / pow((1+ $this->interest),$this->period);
                if($deno==0)$deno=.0001;
                $this->term_pay = ($this->loan_amount * $this->interest) / $deno;
                $interest = $this->loan_amount * $this->interest;
                $this->principal = $this->term_pay - $interest;
                $this->balance = $this->loan_amount - $this->principal;
                
                //make the payment available
                if($i==0){
                    static:: $payment=round($this->term_pay,2);
                }
                
                return array (//"$".number_format(round(($amortArr["schedule"][$monthIn-1]["balance"]), 2),2);
                        'paymentNo'     => $i,
                        'payment' 	=> round($this->term_pay,2),
                        'interest' 	=> number_format(round(($interest), 2),2),
                        'principal' 	=> number_format(round(($this->principal), 2),2),
                        'balance' 	=> round($this->balance,2),
                        );
        }
        public function getSummary(){
                $this->calculate(0);
                $total_pay = $this->term_pay *  $this->period;
                $total_interest = $total_pay - $this->loan_amount;
                return array (
                        'total_pay' => number_format(round(($total_pay), 2),2),
                        'total_interest' => number_format(round(($total_interest), 2),2),
                        );
        }
        public function getInputs(){
                return array (
                        'inputs' => $this->data
                        );
        }
        public function getSchedule(){
                $schedule = array();
                $i=1;
                while  ($this->balance >= 0) {
                        array_push($schedule, $this->calculate($i));
                        $this->loan_amount = $this->balance;
                        $this->period--;
                        $i++;
                }
                static::$amortSched = $schedule;
                
                return $schedule;
        }
}

