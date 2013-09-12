{extends file="parent.tpl"}
{block name=body}
	<h1>Smarty Bigpipe</h1>
	{pagelet id="pagelet1"}
		<h2>pagelet 1</h2>
		<p>
			pagelet 1 content ({$smarty.now|date_format:"%H:%M:%S"})
		</p>
		<p>
			<a href="" rel="pagelet2">reflush pagelet2</a>
		</p>
	{/pagelet}

	{pagelet id="pagelet2"}
		<h2>pagelet 2</h2>
		<p>
			pagelet 2 content ({$smarty.now|date_format:"%H:%M:%S"})
		</p>
		<p>
			<a href="" rel="pagelet1">reflush pagelet1</a>
		</p>
		{script}
			requireLazy(["static.js.jquery"], function($){
				/*alert(1234)*/
			});
		{/script}
	{/pagelet}
{/block}
