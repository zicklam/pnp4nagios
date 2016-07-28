<?php
# ----------------------------------------------------------------------------
# PNP4Nagios Template for check_nagiostats
#
# written by  : Florian Zicklam <github@florianzicklam.de>
# modified by : Florian Zicklam
#
# version     : 0.5~fz
# init date   : 2016-07-25
# last change : 2016-07-28
#
# INFO #######################################################################
# 
# Purpose     :
#
# Description :
#  ./check_nagiostats --TIMERANGE 5
#
#
# CHANGELOG ##################################################################
#
# ----------------------------------------------------------------------------

# Activate debug output (0=disabled 1=activated)
$debugOutput = 0;

# Default values

# Show Graphs:
$displayCheckLatency       = 1;
$displayCheckExecutionTime = 1;
$displayServicePerformance = 1;
$displayServiceStatistics  = 1;
$displayHostPerformance    = 1;
$displayHostStatistics     = 1;


$num_graph = 0;
$schemaChecks = array('','#1F78B4','#A6CEE3','#E31A1C','#FB9A99','#33A02C','#B2DF8A','#FF7F00','#FDBF6F');


if ( $debugOutput >= 1 ) {
  throw new Kohana_exception(print_r(get_defined_vars(),TRUE));
}

if ( $displayCheckLatency == 1 ) {
  $i = 0;
  $num_graph++;

  $ds_name[$num_graph] = "Check Latency";
  $opt[$num_graph]     = "--base 1000 --lower=0 --slope-mode ";
  $opt[$num_graph]    .= "--vertical-label \"ms\" ";
  $opt[$num_graph]    .= "--title \"Check Latency in milliseconds\" ";
  $opt[$num_graph]    .= "--watermark \"© Florian Zicklam - Template: ".basename(__FILE__)."\" ";

  $def[$num_graph] = "";

  foreach ( $this->DS as $KEY=>$VALUE ) {
    if ( preg_match('/(.*)LAT$/', $VALUE['NAME'], $match)) {
      $i++;

      # Format the correct label
      switch ( $match[1] ) {
        case 'AVGACTSVC': $label = "AVG active services";  break;
        case 'AVGPSVSVC': $label = "AVG passive services"; break;
        case 'AVGACTHST': $label = "AVG active hosts";     break;
        case 'AVGPSVHST': $label = "AVG passive hosts";    break;
      }

      $def[$num_graph] .= rrd::def    ("var$KEY", $VALUE['RRDFILE'], $VALUE['DS'], "AVERAGE");
      $def[$num_graph] .= rrd::cdef   ("var_calc$KEY", "var$KEY,1000,/");
      $def[$num_graph] .= rrd::line1  ("var_calc$KEY", rrd::color($i, '', $schemaChecks), rrd::cut($label, 20) ); 
      $def[$num_graph] .= rrd::gprint ("var_calc$KEY", array("LAST", "MAX", "AVERAGE"), "%6.2lf ms");
    }
  }
}

if ( $displayCheckExecutionTime == 1 ) {
  $i = 0;
  $num_graph++;

  $ds_name[$num_graph] = "Check Execution Time";
  $opt[$num_graph]     = "--base 1000 --lower=0 --slope-mode ";
  $opt[$num_graph]    .= "--vertical-label \"ms\" ";
  $opt[$num_graph]    .= "--title \"Check Execution Time in milliseconds\" ";
  $opt[$num_graph]    .= "--watermark \"© Florian Zicklam - Template: ".basename(__FILE__)."\" ";

  $def[$num_graph] = "";

  foreach ( $this->DS as $KEY=>$VALUE ) {
    if ( preg_match('/(.*)EXT$/', $VALUE['NAME'], $match)) {
      $i++;
      switch ( $match[1] ) {
        case 'AVGACTSVC': $label = "AVG active services";  break;
        case 'AVGACTHST': $label = "AVG active hosts"; break;
      }
      $def[$num_graph] .= rrd::def    ("var$KEY", $VALUE['RRDFILE'], $VALUE['DS'], "AVERAGE");
      $def[$num_graph] .= rrd::cdef   ("var_calc$KEY", "var$KEY,1000,/");
      $def[$num_graph] .= rrd::line1  ("var_calc$KEY", rrd::color($i*1.5, '', $schemaChecks), rrd::cut($label, 20) ); 
      $def[$num_graph] .= rrd::gprint ("var_calc$KEY", array("LAST", "MAX", "AVERAGE"), "%8.2lf");
    }
  }
}

# -----------------------------------------------------------------------------
# Service performance data
# -----------------------------------------------------------------------------
if ( $displayServicePerformance == 1) {
	# count up for the next Graph
	$num_graph++;
	
	# Graph configuration
	$ds_name[$num_graph] = "Service Performance in last ".end($this->DS)['ACT']." minute(s)";
	$opt[$num_graph]     = "--base 1000 --lower=0 --slope-mode --alt-autoscale-max ";
	$opt[$num_graph]    .= "--vertical-label \"count\" ";
	$opt[$num_graph]    .= "--title \"Service Performance in last ".end($this->DS)['ACT']." minute(s)\" ";
	$opt[$num_graph]    .= "--watermark \"© Florian Zicklam - Template: ".basename(__FILE__)."\" ";
	
	# Start definition
	$def[$num_graph] = "";
	
	# Define the order for resort the datasource objects:
	$dsOrder = array(
		 0 => 'NUMSACTSVCCHECKS'.end($this->DS)['ACT'].'M',
		 1 => 'NUMACTSVCCHECKS'.end($this->DS)['ACT'].'M',
		 2 => 'NUMOACTSVCCHECKS'.end($this->DS)['ACT'].'M',
		 3 => 'NUMCACHEDSVCCHECKS'.end($this->DS)['ACT'].'M',
	);
	
	# Find all matching datasource objects and save them into temporary array
	foreach ( $this->DS as $KEY => $VALUE ) {
		if ( preg_match('/^NUM(.*)CHECKS'.end($this->DS)['ACT'].'M$/', $VALUE['NAME'], $match)) {
			$arrayServiceStatistics[$KEY] = $VALUE;
		}
	}
	
	# Magic sort function :)
	usort($arrayServiceStatistics, function ($a, $b) use ($dsOrder)  {
		$pos_a = array_search($a['NAME'], $dsOrder);
		$pos_b = array_search($b['NAME'], $dsOrder);
		return $pos_a - $pos_b;
	});
	
	# RRDGraph Legend
	$def[$num_graph] .= rrd::comment(" \l"); 
	$def[$num_graph] .= rrd::comment(str_repeat(" ", 28)); # Label + 3
	$def[$num_graph] .= rrd::comment("\rCurrent ");
	$def[$num_graph] .= rrd::comment("\rAverage ");
	$def[$num_graph] .= rrd::comment("\rMaximum ");
	$def[$num_graph] .= rrd::comment(" \l");
	$def[$num_graph] .= rrd::comment(str_repeat("-", 80));
	
	foreach ( $arrayServiceStatistics as $KEY => $VALUE ) {
		# Only show datasource objects which we used to sort
		if ( in_array($VALUE['NAME'], $dsOrder) ) {
			
			# Get value
			$def[$num_graph] .= rrd::def    ("var$KEY", $VALUE['RRDFILE'], $VALUE['DS'], "AVERAGE");
			
			# Parse special by name (color, label etc.)
			switch ( $VALUE['NAME'] ) {
				case 'NUMSACTSVCCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'Scheduled service checks';
					$def_color        = '#FFFF00';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				case 'NUMACTSVCCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'Active service checks';
					$def_color        = '#00AF33';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				case 'NUMOACTSVCCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'On-demand service checks';
					$def_color        = '#0000CC';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				case 'NUMCACHEDSVCCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'Cached service checks';
					$def_color        = '#FF0000';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				default:
				    $def_label        = 'NOT+NAMED!';
					$def_color        = '#FF1493';
			}
					
			$def[$num_graph] .= rrd::gprint ("var$KEY", "LAST",    "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "AVERAGE", "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "MAX",     "%8.0lf\l");
		}
	}
	
	# Remove all variables at the end
	unset($dsOrder,$arrayServiceStatistics);
}

# -----------------------------------------------------------------------------
# Host performance data
# -----------------------------------------------------------------------------
if ( $displayHostPerformance == 1) {
	# count up for the next Graph
	$num_graph++;
	
	# Graph configuration
	$ds_name[$num_graph] = "Host Performance in last ".end($this->DS)['ACT']." minute(s)";
	$opt[$num_graph]     = "--base 1000 --lower=0 --slope-mode --alt-autoscale-max ";
	$opt[$num_graph]    .= "--vertical-label \"count\" ";
	$opt[$num_graph]    .= "--title \"Host Performance in last ".end($this->DS)['ACT']." minute(s)\" ";
	$opt[$num_graph]    .= "--watermark \"© Florian Zicklam - Template: ".basename(__FILE__)."\" ";
	
	# Start definition
	$def[$num_graph] = "";
	
	# Define the order for resort the datasource objects:
	$dsOrder = array(
		 0 => 'NUMSERHSTCHECKS'.end($this->DS)['ACT'].'M',
		 1 => 'NUMPARHSTCHECKS'.end($this->DS)['ACT'].'M',
		 2 => 'NUMSACTHSTCHECKS'.end($this->DS)['ACT'].'M',
		 3 => 'NUMACTHSTCHECKS'.end($this->DS)['ACT'].'M',
		 4 => 'NUMOACTHSTCHECKS'.end($this->DS)['ACT'].'M',
		 5 => 'NUMCACHEDHSTCHECKS'.end($this->DS)['ACT'].'M',
	);
	
	# Find all matching datasource objects and save them into temporary array
	foreach ( $this->DS as $KEY => $VALUE ) {
		if ( preg_match('/^NUM(.*)CHECKS'.end($this->DS)['ACT'].'M$/', $VALUE['NAME'], $match)) {
			$arrayServiceStatistics[$KEY] = $VALUE;
		}
	}
	
	# Magic sort function :)
	usort($arrayServiceStatistics, function ($a, $b) use ($dsOrder)  {
		$pos_a = array_search($a['NAME'], $dsOrder);
		$pos_b = array_search($b['NAME'], $dsOrder);
		return $pos_a - $pos_b;
	});
	
	# RRDGraph Legend
	$def[$num_graph] .= rrd::comment(" \l"); 
	$def[$num_graph] .= rrd::comment(str_repeat(" ", 28)); # Label + 3
	$def[$num_graph] .= rrd::comment("\rCurrent ");
	$def[$num_graph] .= rrd::comment("\rAverage ");
	$def[$num_graph] .= rrd::comment("\rMaximum ");
	$def[$num_graph] .= rrd::comment(" \l");
	$def[$num_graph] .= rrd::comment(str_repeat("-", 80));
	
	foreach ( $arrayServiceStatistics as $KEY => $VALUE ) {
		# Only show datasource objects which we used to sort
		if ( in_array($VALUE['NAME'], $dsOrder) ) {
			
			# Get value
			$def[$num_graph] .= rrd::def    ("var$KEY", $VALUE['RRDFILE'], $VALUE['DS'], "AVERAGE");
			
			# Parse special by name (color, label etc.)
			switch ( $VALUE['NAME'] ) {
				case 'NUMSERHSTCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'Serial host checks';
					$def_color        = '#7A378B';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				case 'NUMPARHSTCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'Parallel host checks';
					$def_color        = '#B6AFA9';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				case 'NUMSACTHSTCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'Scheduled host checks';
					$def_color        = '#FFFF00';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				case 'NUMACTHSTCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'Active host checks';
					$def_color        = '#00AF33';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				case 'NUMOACTHSTCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'On-demand host checks';
					$def_color        = '#0000CC';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				case 'NUMCACHEDHSTCHECKS'.end($this->DS)['ACT'].'M':
				    $def_label        = 'Cached host checks';
					$def_color        = '#FF0000';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 25), ':STACK');
					break;
				default:
				    $def_label        = 'NOT+NAMED!';
					$def_color        = '#FF1493';
					$def[$num_graph] .= rrd::line2("var$KEY", $def_color, rrd::cut($def_label, 20));
			}
					
			$def[$num_graph] .= rrd::gprint ("var$KEY", "LAST",    "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "AVERAGE", "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "MAX",     "%8.0lf\l");
		}
	}
	
	# Remove all variables at the end
	unset($dsOrder,$arrayServiceStatistics);
}

# -----------------------------------------------------------------------------
# Service statistic data (Service Problems)
# -----------------------------------------------------------------------------
if ( $displayServiceStatistics == 1) {
	# count up for the next Graph
	$num_graph++;
	
	# Graph configuration
	$ds_name[$num_graph] = "Service Statistics";
	$opt[$num_graph]     = "--base 1000 --lower=0 --slope-mode --alt-autoscale-max ";
	$opt[$num_graph]    .= "--vertical-label \"count\" ";
	$opt[$num_graph]    .= "--title \"Service Statistics\" ";
	$opt[$num_graph]    .= "--watermark \"© Florian Zicklam - Template: ".basename(__FILE__)."\" ";
	
	# Start definition
	$def[$num_graph] = "";
	
	# Define the order for resort the datasource objects:
	$dsOrder = array(
		 0 => 'NUMSVCDOWNTIME',
		 1 => 'NUMSVCWARN',
		 2 => 'NUMSVCUNKN',
		 3 => 'NUMSVCCRIT',
		 4 => 'NUMSVCFLAPPING',
		 5 => 'NUMSVCPROB',
	);
	
	# Find all matching datasource objects and save them into temporary array
	foreach ( $this->DS as $KEY => $VALUE ) {
		if ( preg_match('/^NUMSVC(.*)$/', $VALUE['NAME'], $match)) {
			$arrayServiceStatistics[$KEY] = $VALUE;
		}
	}
	
	# Magic sort function :)
	usort($arrayServiceStatistics, function ($a, $b) use ($dsOrder)  {
		$pos_a = array_search($a['NAME'], $dsOrder);
		$pos_b = array_search($b['NAME'], $dsOrder);
		return $pos_a - $pos_b;
	});
	
	# RRDGraph Legend
	$def[$num_graph] .= rrd::comment(" \l"); 
	$def[$num_graph] .= rrd::comment(str_repeat(" ", 23));
	$def[$num_graph] .= rrd::comment("\rCurrent ");
	$def[$num_graph] .= rrd::comment("\rAverage ");
	$def[$num_graph] .= rrd::comment("\rMaximum ");
	$def[$num_graph] .= rrd::comment("\rMinimum ");
	$def[$num_graph] .= rrd::comment(" \l");
	$def[$num_graph] .= rrd::comment(str_repeat("-", 80));
	
	foreach ( $arrayServiceStatistics as $KEY => $VALUE ) {
		# Only show datasource objects which we used to sort
		if ( in_array($VALUE['NAME'], $dsOrder) ) {

			# Get value
			$def[$num_graph] .= rrd::def    ("var$KEY", $VALUE['RRDFILE'], $VALUE['DS'], "AVERAGE");
			
			# Parse special by name (color, label etc.)
			switch ( $VALUE['NAME'] ) {
				case 'NUMSVCDOWNTIME':
				    $def_label        = 'Services Downtime';
					$def_color        = '#B6AFA9';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMSVCWARN':
				    $def_label        = 'Services Warning';
					$def_color        = '#FFFF00';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMSVCUNKN':
				    $def_label        = 'Services Unknown';
					$def_color        = '#FF8000';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMSVCCRIT':
				    $def_label        = 'Services Critical';
					$def_color        = '#FF0000';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMSVCFLAPPING':
				    $def_label        = 'Services Flapping';
					$def_color        = '#000080';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMSVCPROB':
				    $def_label        = 'Total problems';
					$def_color        = '#00B2EE';					
					$def[$num_graph] .= rrd::comment(str_repeat("=", 80));
					$def[$num_graph] .= rrd::line2("var$KEY", $def_color, rrd::cut($def_label, 20));
					break;
				default:
				    $def_label        = 'NOT+NAMED!';
					$def_color        = '#FF1493';
					$def[$num_graph] .= rrd::line2("var$KEY", $def_color, rrd::cut($def_label, 20));
			}
			
			$def[$num_graph] .= rrd::gprint ("var$KEY", "LAST",    "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "AVERAGE", "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "MAX",     "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "MIN",     "%8.0lf \l");
		}
	}

	# Remove all variables at the end
	unset($dsOrder,$arrayServiceStatistics);
}

# -----------------------------------------------------------------------------
# Host statistic data (Service Problems)
# -----------------------------------------------------------------------------
if ( $displayHostStatistics == 1) {
	# count up for the next Graph
	$num_graph++;
	
	# Graph configuration
	$ds_name[$num_graph] = "Host Statistics";
	$opt[$num_graph]     = "--base 1000 --lower=0 --slope-mode --alt-autoscale-max ";
	$opt[$num_graph]    .= "--vertical-label \"count\" ";
	$opt[$num_graph]    .= "--title \"Host Statistics\" ";
	$opt[$num_graph]    .= "--watermark \"© Florian Zicklam - Template: ".basename(__FILE__)."\" ";
	
	# Start definition
	$def[$num_graph] = "";
	
	# Define the order for resort the datasource objects:
	$dsOrder = array(
		 0 => 'NUMHSTDOWNTIME',
		 1 => 'NUMHSTDOWN',
		 2 => 'NUMHSTUNR',
		 3 => 'NUMHSTFLAPPING',
		 4 => 'NUMHSTPROB',
	);
	
	# Find all matching datasource objects and save them into temporary array
	foreach ( $this->DS as $KEY => $VALUE ) {
		if ( preg_match('/^NUMHST(.*)$/', $VALUE['NAME'], $match)) {
			$arrayServiceStatistics[$KEY] = $VALUE;
		}
	}
	
	# Magic sort function :)
	usort($arrayServiceStatistics, function ($a, $b) use ($dsOrder)  {
		$pos_a = array_search($a['NAME'], $dsOrder);
		$pos_b = array_search($b['NAME'], $dsOrder);
		return $pos_a - $pos_b;
	});
	
	# RRDGraph Legend
	$def[$num_graph] .= rrd::comment(" \l"); 
	$def[$num_graph] .= rrd::comment(str_repeat(" ", 23));
	$def[$num_graph] .= rrd::comment("\rCurrent ");
	$def[$num_graph] .= rrd::comment("\rAverage ");
	$def[$num_graph] .= rrd::comment("\rMaximum ");
	$def[$num_graph] .= rrd::comment("\rMinimum ");
	$def[$num_graph] .= rrd::comment(" \l");
	$def[$num_graph] .= rrd::comment(str_repeat("-", 80));
	
	foreach ( $arrayServiceStatistics as $KEY => $VALUE ) {
		# Only show datasource objects which we used to sort
		if ( in_array($VALUE['NAME'], $dsOrder) ) {

			# Get value
			$def[$num_graph] .= rrd::def    ("var$KEY", $VALUE['RRDFILE'], $VALUE['DS'], "AVERAGE");
			
			# Parse special by name (color, label etc.)
			switch ( $VALUE['NAME'] ) {
				case 'NUMHSTDOWNTIME':
				    $def_label        = 'Host Downtime';
					$def_color        = '#B6AFA9';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMHSTUNR':
				    $def_label        = 'Host Unreachable';
					$def_color        = '#FF8000';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMHSTDOWN':
				    $def_label        = 'Host Down';
					$def_color        = '#FF0000';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMHSTFLAPPING':
				    $def_label        = 'Host Flapping';
					$def_color        = '#000080';
					$def[$num_graph] .= rrd::area("var$KEY", $def_color, rrd::cut($def_label, 20), ':STACK');
					break;
				case 'NUMHSTPROB':
				    $def_label        = 'Total problems';
					$def_color        = '#00B2EE';					
					$def[$num_graph] .= rrd::comment(str_repeat("=", 80));
					$def[$num_graph] .= rrd::line2("var$KEY", $def_color, rrd::cut($def_label, 20));
					break;
				default:
				    $def_label        = 'NOT+NAMED!';
					$def_color        = '#FF1493';
					$def[$num_graph] .= rrd::line2("var$KEY", $def_color, rrd::cut($def_label, 20));
			}
			
			$def[$num_graph] .= rrd::gprint ("var$KEY", "LAST",    "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "AVERAGE", "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "MAX",     "%8.0lf");
			$def[$num_graph] .= rrd::gprint ("var$KEY", "MIN",     "%8.0lf \l");
		}
	}

	# Remove all variables at the end
	unset($dsOrder,$arrayServiceStatistics);
}
?>
