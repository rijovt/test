<?php ss
session_start();
require_once('../connection/dbcon.php');
if($_GET['type']=='add')
{
$barcode=$_GET['barcode'];
$qry_stock="select * from tbl_stock where stock_barcode='".$barcode."' and stock_produnit='".$_SESSION['showroom_unit']."'";
$res_stock=mysql_query($qry_stock);
$row_stock=mysql_fetch_array($res_stock);
$itemcategory=$row_stock['stock_item_category'];
$actualrate=$row_stock['stock_price'];
$barcode=$_GET['barcode'];
$itemname=$_GET['itemname'];
$qty=$_GET['qty'];
$unit=$_GET['qtyunit'];
$rate=$_GET['rate'];
$pdiscount=$_GET['discount'];
$discount=(($rate*$pdiscount)/100)*$qty;
$total=$_GET['total'];
$vatamount=0;
if($row_stock['stock_taxable']) //tax calculation
{
$v='s_vat'.$row_stock['stock_taxable'];
$qry_vat="select * from tbl_aj_settings";
$row_tax=mysql_fetch_assoc(mysql_query($qry_vat));
$s_vat=$row_tax[$v];
$vatamount=($total/($s_vat+100))*$s_vat;
}
$type=0;
$offer=0;
if($row_stock['stock_offer']) //offr check
{
$qry_offr=mysql_query("SELECT offer_id FROM tbl_offer where offer_id='".$row_stock['stock_offer']."' and offer_to>NOW()");
if(mysql_num_rows($qry_offr))
	$offer=$row_stock['stock_offer'];
}

$qry="insert into tbl_sales_bill_item (bill_type,item_name,item_category,stock_barcode,item_qty,item_unit,item_actual_rate,item_rate,item_vatamount,item_taxable,item_discount,offer_id,item_total,item_createdby,templ_billid) values ('$type','$itemname','$itemcategory','$barcode','$qty','$unit','$actualrate','$rate','$vatamount','".$row_stock['stock_taxable']."','$discount','$offer','$total','".$_SESSION['current_user']."','".$_SESSION['mybilluniquesession']."')";
$res=mysql_query($qry);
$lastid=mysql_insert_id();

//update tbl_stock subtracting qty
$balqty=$row_stock['stock_qty']-$qty;
$qry_stockupdate=mysql_query("update tbl_stock set stock_qty='$balqty' where stock_barcode='$barcode' and stock_produnit='".$_SESSION['showroom_unit']."'");
}
else if($_GET['type']=='delete')
{
$itemid=$_GET['id'];
$qry_selitem="select stock_barcode,item_qty from tbl_sales_bill_item where item_id='".$itemid."'";
$res_selitem=mysql_query($qry_selitem);
$row_selitem=mysql_fetch_array($res_selitem);
$barcode=$row_selitem['stock_barcode'];
$qty=$row_selitem['item_qty'];

$qry_stock="select stock_qty from tbl_stock where stock_barcode='".$barcode."' and stock_produnit='".$_SESSION['showroom_unit']."'";
$res_stock=mysql_query($qry_stock);
$row_stock=mysql_fetch_array($res_stock);
$newqty=$row_stock['stock_qty']+$qty;
$qry_stockupdate=mysql_query("update tbl_stock set stock_qty='$newqty' where stock_barcode='$barcode' and stock_produnit='".$_SESSION['showroom_unit']."'");
$qry_billitem="delete from tbl_sales_bill_item where item_id='".$itemid."'";
$res_billitem=mysql_query($qry_billitem);
}
$qry_sel="select * from tbl_sales_bill_item where bill_id=0 and item_createdby='".$_SESSION['current_user']."' and templ_billid='".$_SESSION['mybilluniquesession']."'";
$res_sel=mysql_query($qry_sel);
$num_sel=mysql_num_rows($res_sel);
?>
<table border="0" cellspacing="0" cellpadding="0" class="h2-table grid-data" style="width:100%">
<tr class="first">
<td>Sl No</td>
<td>Item</td>
<td>Qty</td>
<td>Rate</td>
<td>Vat</td>
<td>Discount</td>
<td>Amount</td>
<td>Action</td>
</tr>
<input type="hidden" name="hidcount" id="hidcount" value="<?php echo $num_sel; ?>" />
<?php 
$tot_amt=0;
for($i=0;$i<$num_sel;$i++){
$row_sel=mysql_fetch_assoc($res_sel);

?>
<tr <?php if($i%2==0){ ?> class="even"<?php } ?>>
<td><?php echo $i+1; ?></td>
<td><?php echo $row_sel['item_name']; ?></td>
<td><?php echo $row_sel['item_qty']; ?></td>
<td><?php echo $row_sel['item_rate']-$row_sel['item_vatamount']; ?></td>
<td><?php echo $row_sel['item_vatamount']; ?></td>
<td><?php $tot_disc+=$row_sel['item_discount']; echo $row_sel['item_discount']; ?></td>
<td><?php $tot_amt+=$row_sel['item_total']+$row_sel['item_discount']; $tot+=$row_sel['item_total']; echo $row_sel['item_total']; ?></td>
<td><img src="images/delete.png" title="Delete" border="0" style="cursor:pointer" onclick="delete_item(<?php echo $row_sel['item_id'];  ?>)" /></td>
</tr>
<?php } ?>
<tr><td colspan="4"><strong>Total</strong></td><td><strong><?php echo number_format($tot,2);  ?></strong></td></tr>
</table>
<input type="hidden" id="last_item_id" value="<?php echo $lastid;  ?>"  />
<input type="hidden" name="tot_discount" id="tot_discount" value="<?php echo $tot_disc;?>"  />
<input type="hidden" name="totalamount" id="totalamount" value="<?php echo $tot_amt; ?>"  /> 
