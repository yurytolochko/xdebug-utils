# Xdebug Utils

PHP utils to analyze generated files from xdebug profiler and trace.

## Xdebug Proler

	./xdebug profiler [--top 10] [--report report.csv] [--format csv] file1 [ file2 [ folder]]

**Options:**

	-r, --report	Report file
	-f, --format	Report format: table or csv
	-t, --top   	Top number of method to show. Could be number or percentage: 10 or 20%

**Usage:**

	# create full report to csv file
	> ./xdebug profiler -r report.csv cachegrind.out.1340990144_014725

	# display report table with 10 most slow methods
	> ./xdebug profiler -t 10 cachegrind.out.1340990144_014725 cachegrind.out.1340990145_014890

	# display report table with 20% most slow methods
	> ./xdebug profiler -t 20% /var/data/path-to-folder-with-cachegrind-files/


## Xdebug Trace

**coming soon...**