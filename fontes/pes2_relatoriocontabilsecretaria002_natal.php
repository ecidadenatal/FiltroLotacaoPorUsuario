<?
/*
 *     E-cidade Software Publico para Gestao Municipal                
 *  Copyright (C) 2013  DBselller Servicos de Informatica             
 *                            www.dbseller.com.br                     
 *                         e-cidade@dbseller.com.br                   
 *                                                                    
 *  Este programa e software livre; voce pode redistribui-lo e/ou     
 *  modifica-lo sob os termos da Licenca Publica Geral GNU, conforme  
 *  publicada pela Free Software Foundation; tanto a versao 2 da      
 *  Licenca como (a seu criterio) qualquer versao mais nova.          
 *                                                                    
 *  Este programa e distribuido na expectativa de ser util, mas SEM   
 *  QUALQUER GARANTIA; sem mesmo a garantia implicita de              
 *  COMERCIALIZACAO ou de ADEQUACAO A QUALQUER PROPOSITO EM           
 *  PARTICULAR. Consulte a Licenca Publica Geral GNU para obter mais  
 *  detalhes.                                                         
 *                                                                    
 *  Voce deve ter recebido uma copia da Licenca Publica Geral GNU     
 *  junto com este programa; se nao, escreva para a Free Software     
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA          
 *  02111-1307, USA.                                                  
 *  
 *  Copia da licenca no diretorio licenca/licenca_en.txt 
 *                                licenca/licenca_pt.txt 
 */
require_once(modification("fpdf151/pdf.php"));
require_once(modification("libs/db_sql.php"));
require_once(modification("model/pessoal/std/DBPessoal.model.php"));

$clrotulo = new rotulocampo;

parse_str($HTTP_SERVER_VARS['QUERY_STRING']);
//db_postmemory($HTTP_SERVER_VARS,2);exit;

$arquivo = '';

if($folha == 'r14'){
  $head7 = "CALCULO : SALÁRIO";
  $arquivo = 'gerfsal';
}elseif($folha == 'r35'){
  $head7 = "CALCULO : 13o. SALÁRIO";
  $arquivo = 'gerfs13';
}elseif($folha == 'r48'){
  $head7 = "CALCULO : COMPLEMENTAR";
  $arquivo = 'gerfcom';
}elseif($folha == 'r20'){
  $head7 = "CALCULO : RESCISÃO";
  $arquivo = 'gerfres';
}

$head3 = "RELATÓRIO CONTÁBIL";
$head5 = "MÊS : ".$mes." / ".$ano;


$oDadosLotacoesPorUsuario = DBPessoal::buscaLotacoesPorUsuario(null, null, null, null, "S");
if ($oDadosLotacoesPorUsuario->lErro) {
	db_redireciona("db_erros.php?fechar=true&db_erro={$oDadosLotacoesPorUsuario->sMsg}");
}
$sWhereLotacoesPorUsuario = "r70_estrut ~ '^(".implode('|',$oDadosLotacoesPorUsuario->aEstruturais).")'";

if(db_getsession('DB_instit') == 1){

$sql = "
select lotacao,o56_elemento, o56_descr, r70_descr , sum(bruto) as bruto
from ( 
select case lotacao
            when '99000' then 'FUNDEB' 
            when '98000' then 'TEMPOR' 
        else lotacao end as lotacao, 
       case when lotacao IN ('99000','98000') then 'SME' else r70_descr end as r70_descr, 
       case when lotacao in ('25000', '42000', '98000', '43000') and o56_elemento in ('3319011990000', '3319009990000', '3319016990000')
            then '3319004990000'
            else o56_elemento
       end as o56_elemento, 
       case when lotacao in ('25000', '42000', '98000', '43000') and o56_elemento in ('3319011990000', '3319009990000', '3319016990000')
            then 'OUTROS - CONTRATO TEMPO DETERMINADO'
            else o56_descr
       end as o56_descr, 
       bruto
from (
select substr(r70_estrut,1,2)||'000' as lotacao,
       o56_elemento,
       o56_descr,
       round(sum({$folha}_valor),2) as bruto
from
(
select case  
            when r70_estrut = '09116' then '99000' 
            when r70_estrut = '09120' then '98000' 
            when r70_estrut = '09110' then '98000' 
            when r70_estrut = '09119' then '98000' 
            
       else r70_estrut end as r70_estrut,
       case when '{$folha}' = 'r20' then '3319094990000' else o56_elemento end as o56_elemento,
       case when '{$folha}' = 'r20' then 'INDENIZAÇÕES E RESTITUIÇÕES TRABALHISTAS' else o56_descr end as o56_descr,
       {$folha}_valor
from $arquivo
     inner join rhrubricas    on rh27_rubric = {$folha}_rubric
                             and rh27_instit = {$folha}_instit
     left join rhrubelemento  on rh27_rubric = rh23_rubric
                             and rh27_instit = rh23_instit
     left  join orcelemento   on o56_codele  = rh23_codele
                             and o56_anousu  = {$folha}_anousu
     inner join rhpessoalmov  on rh02_regist = {$folha}_regist
                             and rh02_anousu = {$folha}_anousu
                             and rh02_mesusu = {$folha}_mesusu 
     inner join rhlota        on r70_codigo  = rh02_lota
                             and r70_instit  = rh02_instit
where {$folha}_anousu = $ano
  and {$folha}_mesusu = $mes
  and {$folha}_pd     = 1 
  and {$folha}_instit = ".db_getsession('DB_instit')."
  and {$sWhereLotacoesPorUsuario}		
) as x
group by substr(r70_estrut,1,2), o56_elemento, o56_descr
order by substr(r70_estrut,1,2), o56_elemento, o56_descr
) yyy
left join rhlota on lotacao = r70_estrut
                and r70_instit = ".db_getsession('DB_instit').") as yyy
group by lotacao,r70_descr,o56_elemento, o56_descr 
order by lotacao, o56_elemento, o56_descr";


$sql_deducao = "
select case lotacao
            when '99000' then 'FUNDEB' 
            when '98000' then 'TEMPOR' 
        else lotacao end as lotacao, 
       case when lotacao IN ('99000','98000') then 'SME' else r70_descr end as r70_descr, 
       rh27_rubric, 
       rh27_descr, 
       deducao
from (
select substr(r70_estrut,1,2)||'000' as lotacao,
       rh27_rubric,
       rh27_descr,
       round(sum({$folha}_valor),2) as deducao
from
(
select case  
            when r70_estrut = '09116' then '99000' 
            when r70_estrut = '09120' then '98000' 
            when r70_estrut = '09110' then '98000' 
            when r70_estrut = '09119' then '98000' 
            
       else r70_estrut end as r70_estrut,
       rh27_rubric,
       rh27_descr ,
       {$folha}_valor
from $arquivo
     inner join rhrubricas    on rh27_rubric = {$folha}_rubric
                             and rh27_instit = {$folha}_instit
     inner join rhpessoalmov  on rh02_regist = {$folha}_regist
                             and rh02_anousu = {$folha}_anousu
                             and rh02_mesusu = {$folha}_mesusu 
     inner join rhlota        on r70_codigo  = rh02_lota
                             and r70_instit  = rh02_instit
where {$folha}_anousu = $ano
  and {$folha}_mesusu = $mes
  and {$folha}_rubric in (select r09_rubric from basesr where r09_anousu = $ano and r09_mesusu = $mes and r09_base = 'B045' and r09_instit = ".db_getsession('DB_instit')." ) 
  and {$folha}_instit = ".db_getsession('DB_instit')."
  and {$sWhereLotacoesPorUsuario}
) as x
group by substr(r70_estrut,1,2), rh27_rubric, rh27_descr
order by substr(r70_estrut,1,2), rh27_rubric, rh27_descr
) yyy
left join rhlota on lotacao = r70_estrut
                and r70_instit = ".db_getsession('DB_instit');


}else{

$sql = "
select lotacao,
       case lotacao 
            when 'A' then 'Ativos'
            when 'I' then 'Inativos'
            when 'P' then 'Pensionistas'
       end as r70_descr, 
       o56_elemento, 
       o56_descr, 
       bruto
from (
select r70_estrut as lotacao,
       o56_elemento,
       o56_descr,
       round(sum({$folha}_valor),2) as bruto
from
(
select rh30_vinculo as r70_estrut,  
       case when rh30_vinculo = 'I' and rh23_codele in (9440, 9476, 9489)
         then 9385 else  
         case when rh30_vinculo = 'P' and rh23_codele in (9440, 9476, 9489, 9385)
            then 9685
            else rh23_codele      
       end end as rh23_codele,
       {$folha}_anousu,
       {$folha}_valor
from $arquivo
     inner join rhpessoalmov  on {$folha}_anousu  = rh02_anousu
                             and {$folha}_mesusu  = rh02_mesusu
                             and {$folha}_regist  = rh02_regist
                             and {$folha}_instit  = rh02_instit
     inner join rhregime      on rh30_codreg = rh02_codreg
                             and rh30_instit = rh02_instit
     inner join rhrubricas    on rh27_rubric = {$folha}_rubric
                             and rh27_instit = {$folha}_instit
     left join rhrubelemento  on rh27_rubric = rh23_rubric
                             and rh27_instit = rh23_instit
     inner join rhlota        on r70_codigo  = rh02_lota
                             and r70_instit  = rh02_instit
where {$folha}_anousu = $ano
  and {$folha}_mesusu = $mes
  and {$folha}_pd = 1
  and {$folha}_instit = ".db_getsession('DB_instit')."
  and {$sWhereLotacoesPorUsuario}
) as x
left join orcelemento   on o56_codele  = rh23_codele
                       and o56_anousu  = {$folha}_anousu
group by r70_estrut, o56_elemento, o56_descr
order by r70_estrut, o56_elemento, o56_descr
) yyy";

$sql_deducao = "
select lotacao,
       case lotacao 
            when 'A' then 'Ativos'
            when 'I' then 'Inativos'
            when 'P' then 'Pensionistas'
       end as r70_descr, 
       rh27_rubric, 
       rh27_descr, 
       deducao
from (
select r70_estrut as lotacao,
       rh27_rubric,
       rh27_descr,
       round(sum({$folha}_valor),2) as deducao
from
(
select rh30_vinculo as r70_estrut, 
       rh27_rubric,
       rh27_descr, 
       {$folha}_valor
from $arquivo
     inner join rhpessoalmov  on {$folha}_anousu  = rh02_anousu
                             and {$folha}_mesusu  = rh02_mesusu
                             and {$folha}_regist  = rh02_regist
                             and {$folha}_instit  = rh02_instit
     inner join rhregime      on rh30_codreg = rh02_codreg
                             and rh30_instit = rh02_instit
     inner join rhrubricas    on rh27_rubric = {$folha}_rubric
                             and rh27_instit = {$folha}_instit
     inner join rhlota        on r70_codigo  = {$folha}_lotac::int
                             and r70_instit  = {$folha}_instit
where {$folha}_anousu = $ano
  and {$folha}_mesusu = $mes
  and {$folha}_rubric in (select r09_rubric from basesr where r09_anousu = $ano and r09_mesusu = $mes and r09_base = 'B045' and r09_instit = ".db_getsession('DB_instit')." ) 
  and {$folha}_instit = ".db_getsession('DB_instit')."
  and {$sWhereLotacoesPorUsuario}
) as x
group by r70_estrut, rh27_rubric, rh27_descr
order by r70_estrut, rh27_rubric, rh27_descr
) yyy";

}
$result = pg_exec($sql);
// db_criatabela($result);exit;

$xxnum = pg_numrows($result);
if ($xxnum == 0){
   db_redireciona('db_erros.php?fechar=true&db_erro=Não existem Códigos cadastrados no período de '.$mes.' / '.$ano);

}

$pdf = new PDF(); 
$pdf->Open(); 
$pdf->AliasNbPages(); 
$total = 0;
$pdf->setfillcolor(235);
$pdf->setfont('arial','b',8);
$troca = 1;
$alt = 6;
$total_1_dia   = 0;
$total_2_dia   = 0;
$total_3_dia   = 0;
$total_geral   = 0;
$troca_lotacao = '';
$pre           = 1;
$total_teste_geral = 0;
$total_teste_deducao = 0;
for($x = 0; $x < pg_numrows($result);$x++){
   db_fieldsmemory($result,$x);

   if($troca_lotacao != $lotacao ){
      $troca = 1;
      $pre == 1?$pre=0:$pre=1;
      $pdf->setfont('arial','b',8);
      $pdf->cell(140,$alt,'',0,0,"R",$pre);
      $pdf->cell(25,$alt,db_formatar($total_geral,'f'),0,1,"R",$pre);

      $sql_deducao_lotacao_analitica = "select rh27_rubric, rh27_descr, deducao from ($sql_deducao) as xx where lotacao = '$troca_lotacao'";
      $res_deducao_lotacao_analitica = db_query($sql_deducao_lotacao_analitica);
      if(pg_numrows($res_deducao_lotacao_analitica) > 0){

        $pdf->ln(20);
        $pre = 1;
        $pdf->cell(125,$alt-1,'DEMONSTRATIVO DE CONSIGNAÇÕES NÃO EFETIVAS',0,1,'C', $pre);
        $pdf->cell(125,$alt-1,'INSTRUÇÃO NORMATIVA 01/2016 - CGM',0,1,'C', $pre);
        $pdf->ln(4);
        $pdf->cell(20,$alt,'RUBRICA',1,0,'C', 1);
        $pdf->cell(80,$alt,'DESCRIÇÃO',1,0,'C', 1);
        $pdf->cell(25,$alt,'VALOR',1,1,'C', 1);
        $total_deducoes_analiticas = 0;
        $pdf->setfont('arial','',8);
        $pre = 0;
        for($xy = 0; $xy < pg_numrows($res_deducao_lotacao_analitica);$xy++){
          db_fieldsmemory($res_deducao_lotacao_analitica,$xy);
          $pdf->cell(20,$alt, $rh27_rubric,0,0,'L', $pre);
          $pdf->cell(80,$alt, $rh27_descr,0,0,'L', $pre);
          $pdf->cell(25,$alt, db_formatar($deducao,'f'),0,1,'R', $pre);
          $total_deducoes_analiticas += $deducao;
          $total_teste_deducao += $deducao;
          $pre == 1?$pre=0:$pre=1;
        }
        $pdf->setfont('arial','B',8);
        $pdf->cell(100,$alt, 'T O T A L ',0,0,'C', $pre);
        $pdf->cell(25,$alt, db_formatar($total_deducoes_analiticas,'f'),0,1,'R', $pre);

      }
      

      $total_geral = 0;
      $troca_lotacao = $lotacao;
   }


   if ($pdf->gety() > $pdf->h - 30 || $troca != 0 ){
      $pdf->addpage();
      $pdf->setfont('arial','b',8);
      $pdf->cell(0,$alt,$lotacao.' - '.$r70_descr,0,1,"L",0);
      $pdf->cell(20,$alt-1,'Elemento','LRT',0,"C",1);
      $pdf->cell(60,$alt-1,'Descrição','LRT',0,"C",1);
      $pdf->cell(10,$alt-1,'Cód.','LRT',0,"C",1);
      $pdf->cell(25,$alt-1,'Valor','LRT',0,"C",1);
      $pdf->cell(25,$alt-1,'Consignações','LRT',0,"C",1);
      $pdf->cell(25,$alt-1,'Liquidar','LRT',1,"C",1);
      $pdf->cell(20,$alt-1,'','LRB',0,"C",1);
      $pdf->cell(60,$alt-1,'','LRB',0,"C",1);
      $pdf->cell(10,$alt-1,'','LRB',0,"C",1);
      $pdf->cell(25,$alt-1,'','LRB',0,"C",1);
      $pdf->cell(25,$alt-1,'Não Efetivas','LRB',0,"C",1);
      $pdf->cell(25,$alt-1,'','LRB',1,"C",1);

      $troca = 0;
      $pre = 1;
      $sql_deducao_lotacao = "select round(sum(deducao),2) as deducao from ($sql_deducao) as xx where lotacao = '$lotacao'";
      $res_deducao_lotacao = db_query($sql_deducao_lotacao);
      if(pg_numrows($res_deducao_lotacao) > 0){
        db_fieldsmemory($res_deducao_lotacao,0);
      }else{
        $deducao = 0;
      }
   }

   $pre == 1?$pre=0:$pre=1;

   if($lotacao == '25000' || $lotacao == '42000' || $lotacao ==  'TEMPOR' || $lotacao == '43000'){ 
     if($o56_elemento == '3319011990000'
      ||$o56_elemento == '3319009990000'
      ||$o56_elemento == '3319016990000'
       ){
       $o56_elemento = '3319004990000';
       $o56_descr    = 'OUTROS - CONTRATO TEMPO DETERM';
     }
   }
   $pdf->setfont('arial','',7);
   $pdf->cell(20,$alt,$o56_elemento,0,0,"L",$pre);
   $pdf->cell(60,$alt,$o56_descr,0,0,"L",$pre);
   $pdf->cell(10,$alt,'111',0,0,"L",$pre);
   $pdf->cell(25,$alt,db_formatar($bruto,'f'),0,0,"R",$pre);
   if($o56_elemento == '3319011990000'
    ||$o56_elemento == '3319004990000'
    ||$o56_elemento == '3339003990000'
    ||$o56_elemento == '3319001990000'
    ||$o56_elemento == '3319094990000'
     ){
     $valor_deducao = $deducao;
     $pdf->cell(25,$alt,db_formatar($deducao,'f'),0,0,"R",$pre);
   }else{
     $valor_deducao = 0;
     $pdf->cell(25,$alt,db_formatar(0,'f'),0,0,"R",$pre);
   }
  $pdf->cell(25,$alt,db_formatar(($bruto - $valor_deducao),'f'),0,1,"R",$pre);

   $total_geral += ( $bruto - $valor_deducao );
   $total_1_dia += ( $bruto - $valor_deducao );   
   $total_teste_geral += $bruto;

}
$pdf->setfont('arial','b',8);
$pdf->cell(140,$alt,'',0,0,"R",$pre);
$pdf->cell(25,$alt,db_formatar($total_geral,'f'),0,1,"R",$pre);

$sql_deducao_lotacao_analitica = "select rh27_rubric, rh27_descr, deducao from ($sql_deducao) as xx where lotacao = '$troca_lotacao'";
$res_deducao_lotacao_analitica = db_query($sql_deducao_lotacao_analitica);
if(pg_numrows($res_deducao_lotacao_analitica) > 0){

  $pdf->ln(20);
  $pre = 1;
  $pdf->cell(125,$alt-1,'DEMONSTRATIVO DE CONSIGNAÇÕES NÃO EFETIVAS',0,1,'C', $pre);
  $pdf->cell(125,$alt-1,'INSTRUÇÃO NORMATIVA 01/2016 - CGM',0,1,'C', $pre);
  $pdf->ln(4);
  $pdf->cell(20,$alt,'RUBRICA',1,0,'C', 1);
  $pdf->cell(80,$alt,'DESCRIÇÃO',1,0,'C', 1);
  $pdf->cell(25,$alt,'VALOR',1,1,'C', 1);
  $total_deducoes_analiticas = 0;
  $pdf->setfont('arial','',8);
  $pre = 0;
  for($xy = 0; $xy < pg_numrows($res_deducao_lotacao_analitica);$xy++){
    db_fieldsmemory($res_deducao_lotacao_analitica,$xy);
    $pdf->cell(20,$alt, $rh27_rubric,0,0,'L', $pre);
    $pdf->cell(80,$alt, $rh27_descr,0,0,'L', $pre);
    $pdf->cell(25,$alt, db_formatar($deducao,'f'),0,1,'R', $pre);
    $total_deducoes_analiticas += $deducao;
    $total_teste_deducao += $deducao;
   
    $pre == 1?$pre=0:$pre=1;
  }
  $pdf->setfont('arial','B',8);
  $pdf->cell(100,$alt, 'T O T A L ',0,0,'C', $pre);
  $pdf->cell(25,$alt, db_formatar($total_deducoes_analiticas,'f'),0,1,'R', $pre);

}

/*
$pdf->setfont('arial','b',10);
$pdf->cell(40,$alt,'TOTAL GERAL',1,0,"L",$pre);
$pdf->cell(30,$alt,db_formatar($total_1_dia,'f'),1,0,"R",$pre);
$pdf->cell(30,$alt,db_formatar($total_2_dia,'f'),1,0,"R",$pre);
$pdf->cell(30,$alt,db_formatar($total_3_dia,'f'),1,0,"R",$pre);
$pdf->cell(30,$alt,db_formatar($total_geral,'f'),1,1,"R",$pre);
$pdf->cell(30,$alt,db_formatar($total_1_dia,'f'),1,0,"R",$pre);
*/

$pdf->Output();
   
?>
