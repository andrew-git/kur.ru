<?php
Yii::app()->clientScript->registerCoreScript('jquery')->registerCoreScript('jquery.ui');

Yii::app()->clientScript->registerScriptFile('/js/debug.js');

Yii::app()->clientScript->registerScriptFile('/js/d3/d3.v2.js');

Yii::app()->clientScript->registerCssFile('/css/site/bootstrap/css/bootstrap.css');
Yii::app()->clientScript->registerCssFile('/css/site/bootstrap/css/bootstrap-responsive.css');
?>

<div class="toolbar-sidebar well">
    <div class="nodes-holder"></div>
    <div class="links-holder"></div>
</div>
<div class="navbar navbar-fixed-top toolbar-top">
    <div class="navbar-inner">
        <div class="container">
            <div class="nav-collapse">
                <ul class="nav">
                    <li>
                        <form id="search-form" class="form-search">
                            <?php $this->widget('CAutoComplete', array(
                            'name'       => 'search',
                            'url'        => array('/run/autocomplete'),
                            'max'        => 20, //specifies the max number of items to display
                            'minChars'   => 2,
                            'delay'      => 100, //number of milliseconds before lookup occurs
                            'matchCase'  => false, //match case when performing a lookup?
                            'htmlOptions'=> array("class"=> "input-medium search-query"),
                        ));
                            ?>
                            <button type="submit" class="btn">Поиск</button>
                        </form>
                    </li>
                </ul>
                <ul class="nav">
                    <li>
                        <div class="btn" id="closer"><i class="icon-plus-sign"></i></div>
                    </li>
                    <li>
                        <div class="btn" id="further"><i class="icon-minus-sign"></i></div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div id="chart" style=""></div>

<script type="text/javascript">
//var fill = d3.scale.category20();

var w = $(document).width(),
    h = $(document).height();

var svg = d3.select("body").append("svg:svg")
    .attr("width", w)
    .attr("height", h);

var chart = svg.attr("pointer-events", "all")
    .append('svg:g')
    .call(d3.behavior.zoom().on("zoom", function()
{
    chart.attr("transform",
        "translate(" + d3.event.translate + ")"
            + " scale(" + d3.event.scale + ")"
    );
}));

var force = d3.layout.force()
    .gravity(.15)
    .linkDistance(70)
    .charge(-600)
    .theta(-9.9)
    .size([w, h]);

var circle, path, text, plus, cancel;
var nodes = [];
var links = [];

// Use elliptical arc path segments to doubly-encode directionality.
var tick = function()
{
    !path || path.attr("d", function(d)
    {
        //            if (d.type == 'sinonim')
        //            {
        //                d.target.px = d.target.x = d.source.x;
        //                d.target.py = d.target.y = d.source.y + 15;
        //            }
        //            if (d.type == 'english')
        //            {
        //                d.target.px = d.target.x =  d.source.x;
        //                d.target.py = d.target.y =  d.source.y - 20;
        //            }

        //        var dx = d.target.x - d.source.x,
        //            dy = d.target.y - d.source.y,
        //            dr = Math.sqrt(dx * dx + dy * dy);
        //        return "M" + d.source.x + "," + d.source.y + "A" + dr + "," + dr + " 0 0,1 " + d.target.x + "," + d.target.y;
    })
        .attr("x1", function(d)
        {
            return d.source.x;
        })
        .attr("y1", function(d)
        {
            return d.source.y;
        })
        .attr("x2", function(d)
        {
            return d.target.x;
        })
        .attr("y2", function(d)
        {
            return d.target.y;
        })
    ;

    g.attr("transform", function(d)
    {
        if (d == undefined)
        {
            return false;
        }
        var y = d.y > 0 ? d.y : d.y / 5;
        return "translate(" + d.x + "," + y + ")";
    });
};

var nodesShow = function(g)
{
    g.append("svg:circle")
        .attr("class", "node")
        .attr("r", 6)
        .on('click', function()
        {
            alert(3);
            $(this).remove();
            d3.json('/run/get/id/' + $(this).parent().data('id'), update);
        });

    g.append("svg:text")
        .attr("x", 8)
        .attr("y", ".31em")
        .text(function(d)
        {
            return d.title;
        });

    g.append("svg:a")
        .attr('width', 10)
        .attr('height', 10)
        .text("x")
        .attr('class', 'cancel')
        .attr('id', function(d) {return 'cancel_'+d.name})
        .attr("x", 1)
        .attr("y", -6);

    g.append('svg:text')
        .attr('id', function(d) {return 'plus_'+d.name})
        .attr('class', 'plus')
        .attr('width', 20)
        .attr('height', 20)
        .attr("x", -10)
        .attr("y", -4)
        .text(function(node)
        {
            return node.e_count > node.visible_edge_count && !node.opened ? '+' : '';
        });

};

var addNodesLinks = function(json)
{
    // Compute the distinct nodes from the links.
    json.nodes.forEach(function(node)
    {
        nodes[node.name] || (nodes[node.name] = node);
    });

    json.links.forEach(function(link)
    {
        link.source = nodes[link.source] || (nodes[link.source] = {name: link.source});
        link.target = nodes[link.target] || (nodes[link.target] = {name: link.target});

        var add = true;
        links.forEach(function(link2)
        {
            if (link2.source == link.source && link2.target == link.target)
            {
                add = false;
            }
        });
        if (add)
        {
            links.push(link);
        }
    });
};

var update = function(json)
{
    addNodesLinks(json);
    //toolbar interfaces
    /*
    var holder = $('.nodes-holder').empty();
    var lholder = $('.links-holder').empty();
    $.each(d3.values(nodes), function(i, el)
    {
        holder.append($('<div>').text(el.title).data('id', el.name));
    });
    $.each(links, function(i, el)
    {
        lholder.append($('<div>').text(el.source.name + ' : ' + el.target.name));
    });
    */

    force
        .nodes(d3.values(nodes))
        .links(links);

    // Update the paths…
    path = chart.selectAll("line.link").data(force.links());
    path.enter().append("line")
        .attr("class", function(d)
        {
            return "link " + d.type;
        })
        .attr("marker-end", function(d)
        {
            if (d.type == 'subclass_of')
            {
                return "url(#suit)";
            }
            else
            {
                return '';
            }
        });
    path.exit().remove();

    // Update the nodes…
    g = chart.selectAll("g").data(force.nodes());
    g.enter().append("svg:g")
        .attr("class", "node")
        .attr("data-id", function(d)
        {
            return d.name
        });
    g.exit().remove();
    nodesShow(g);


};

var progressBar = $('<div class="progress progress-striped progress-info active"><div class="bar" style="width: 100%;"></div></div>');
$('#search-form').submit(function()
{
    var self = $(this);
    var pb = progressBar.clone().insertAfter(self);
    $.get('/run/search', $(this).serialize(),
        function(data)
        {
            self.show();
            pb.remove();
            update(data);
            force.start();
        }, 'json');

    self.hide();
    pb.show();
    return false;
});

force.on("tick", tick);


$('#closer').click(function()
{
    chart.mousewheel();
    return false;
});
$('#further').click(function()
{
    return false;
});

</script>