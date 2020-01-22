<?php

namespace App\Console\Commands;

use File;
use Config;
use App\Client;
use App\Campaign;
use App\Invoice;
use XeroPrivate;
use Illuminate\Console\Command;

class ImportInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ImportInvoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

		
       	$clients = Client::select('xero_id')->get()->toArray();
       	$campaigns = Campaign::select('xero_id')->get()->toArray();
		$data_accounts= array();
		$accounts = array_merge($clients,$campaigns);
			foreach($accounts as $account){
			$data_accounts[] = $account['xero_id'];
			}
		$data_accounts = array_unique($data_accounts);	
		foreach($data_accounts as $account){
		
			
			$client_invoices = Invoice::where('xero_id',$account)->get()->pluck('InvoiceNumber')->toArray();
			
			if(isset($account)){
				
				$invoices = XeroPrivate::load('Accounting\\Invoice')->setParameter('ContactIDs',$account)->execute();
					//echo '<pre>' ; print_r($invoices) ; die;
					foreach($invoices as $invoice){
						//echo '<pre>' ; print_r($invoice) ; die;
						
							$invoiceData = array();
							$invoiceData['xero_id'] = $account;
							$invoiceData['InvoiceNumber'] = $invoice->InvoiceNumber;
							$invoiceData['InvoiceID'] = $invoice->InvoiceID;
							$invoiceData['Status'] = $invoice->Status;
							$contact = array();
							if(isset($invoice->Contact)){
								$contact['id'] = $invoice->Contact->ContactID;
								$contact['ContactNumber'] = $invoice->Contact->ContactNumber;
								$contact['AccountNumber'] = $invoice->Contact->AccountNumber;
								$contact['ContactStatus'] = $invoice->Contact->ContactStatus;
								$contact['Name'] = $invoice->Contact->Name;
								$contact['FirstName'] = $invoice->Contact->FirstName;
								$contact['LastName'] = $invoice->Contact->LastName;
								$contact['EmailAddress'] = $invoice->Contact->EmailAddress;
								$contact['SkypeUserName'] = $invoice->Contact->SkypeUserName;
								$contact['ContactPersons'] = $invoice->Contact->ContactPersons;
								$contact['Website'] = $invoice->Contact->Website;
								$contact['Addresses'] = $invoice->Contact->Addresses;
								$contact['TaxNumber'] = $invoice->Contact->TaxNumber;
								$contact['IsSupplier'] = $invoice->Contact->IsSupplier;
								$contact['IsCustomer'] = $invoice->Contact->IsCustomer;
								$contact['Discount'] = $invoice->Contact->Discount;
								$contact['Balances'] = $invoice->Contact->Balances;
							}
							$invoiceData['contact'] = json_encode($contact);
							$invoiceData['LineAmountTypes'] = $invoice->LineAmountTypes;
							$invoiceData['Reference'] = $invoice->Reference;
							$invoiceData['CurrencyCode'] = $invoice->CurrencyCode;
							$invoiceData['CurrencyRate'] = $invoice->CurrencyRate;
							$invoiceData['SubTotal'] = $invoice->SubTotal;
							$invoiceData['TotalTax'] = $invoice->TotalTax;
							$invoiceData['Total'] = $invoice->Total;
							$invoiceData['TotalDiscount'] = $invoice->TotalDiscount;
							$invoiceData['HasAttachments'] = $invoice->HasAttachments;
							$invoiceData['AmountDue'] = $invoice->AmountDue;
							$invoiceData['AmountPaid'] = $invoice->AmountPaid;
							
							$FullyPaidOnDate = array();
							
							if(isset($invoice->FullyPaidOnDate)){
								$FullyPaidOnDate = json_decode(json_encode($invoice->FullyPaidOnDate),true);
							}
							$invoiceData['FullyPaidOnDate'] = json_encode($FullyPaidOnDate);
							
							$date = array();
							
							if(isset($invoice->Date)){
								$date = json_decode(json_encode($invoice->Date),true);
							}
							
							$invoiceData['date'] = json_encode($date);
							
							$DueDate = array();
							
							if(isset($invoice->DueDate)){
								$DueDate['date'] = json_decode(json_encode($invoice->DueDate),true);
								
							}
							
							$invoiceData['DueDate'] = json_encode($DueDate);
							
							$LineItems = array();
							
							if(isset($invoice->LineItems)){
								$counter = 0;
								
								foreach($invoice->LineItems as $item){
									$LineItems[$counter]['Description'] = $item->Description;
									$LineItems[$counter]['Quantity'] = $item->Quantity;
									$LineItems[$counter]['UnitAmount'] = $item->UnitAmount;
									$LineItems[$counter]['ItemCode'] = $item->ItemCode;
									$LineItems[$counter]['AccountCode'] = $item->AccountCode;
									$LineItems[$counter]['LineItemID'] = $item->LineItemID;
									$LineItems[$counter]['TaxType'] = $item->TaxType;
									$LineItems[$counter]['TaxAmount'] = $item->TaxAmount;
									$LineItems[$counter]['LineAmount'] = $item->LineAmount;
									$LineItems[$counter]['Tracking'] = $item->Tracking;
									$LineItems[$counter]['DiscountRate'] = $item->DiscountRate;
									
									$counter++;
								}
								
							}
							
							$invoiceData['LineItems'] = json_encode($LineItems);
							
							$Payments = array();
							
							if(isset($invoice->Payments)){
								
								$counter = 0;
								
								foreach($invoice->Payments as $payment){
									$Payments[$counter]['PaymentID'] = $payment->PaymentID;
									$Payments[$counter]['PaymentType'] = $payment->PaymentType;
									$Payments[$counter]['Invoice'] = $payment->Invoice;
									$Payments[$counter]['CreditNote'] = $payment->CreditNote;
									$Payments[$counter]['Prepayment'] = $payment->Prepayment;
									$Payments[$counter]['Overpayment'] = $payment->Overpayment;
									$Payments[$counter]['Account'] = $payment->Account;
									$Payments[$counter]['CurrencyRate'] = $payment->CurrencyRate;
									$Payments[$counter]['Reference'] = $payment->Reference;
									$Payments[$counter]['Status'] = $payment->Status;
									
									$date = array();
							
									if(isset($payment->Date)){
										$date['date'] =  json_decode(json_encode($invoice->Date),true);
										
									}
									$Payments[$counter]['date'] = json_encode($date);
									$counter++;
								}
								
							}
							$invoiceData['Payments'] = json_encode($Payments);
							
							//echo '<pre>' ; print_r($invoiceData) ; die;
							if(in_array($invoice->InvoiceNumber,$client_invoices)){
								$invoice = Invoice::where('InvoiceNumber',$invoice->InvoiceNumber)->first();
								$invoice->update($invoiceData);
							}else{
								$invoice = Invoice::create($invoiceData);
							}
							
							$data = XeroPrivate::loadByGUID('Accounting\\Invoice',$invoice->InvoiceNumber);
								$attachments = $data->getAttachments();
								
								foreach ($attachments as $attachment) {
									
									if(strpos($attachment->getFileName(),$invoice->InvoiceNumber)){
									
										if(!file_exists(Config::get('constant.invoices_files_path').'/'.$attachment->getFileName())){
										
											file_put_contents(Config::get('constant.invoices_files_path').'/'.$attachment->getFileName(), $attachment->getContent());
										}
									}else{
										if(!file_exists(Config::get('constant.invoices_files_path').'/'.$invoice->InvoiceNumber.'_'.$attachment->getFileName())){
											file_put_contents(Config::get('constant.invoices_files_path').'/'.$invoice->InvoiceNumber.'_'.$attachment->getFileName(), $attachment->getContent());
										
										}
									}
							}
						
					}
			}
		
		}
		return true;
    }
}
