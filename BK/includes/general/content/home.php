<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 14/10/2016
 * Time: 19:44
 */
?>
<div class="row">
    <div class="col-lg-8">
        <div class="row">
            <div class="col-lg-4">
                <div class="widget style1 navy-bg">
                    <div class="row">
                        <div class="col-xs-4">
                            <i class="fa fa-cloud fa-5x"></i>
                        </div>
                        <div class="col-xs-8 text-right">
                            <span> Today degrees </span>
                            <h2 class="font-bold">26'C</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="widget style1 red-bg">
                    <div class="row">
                        <div class="col-xs-4">
                            <i class="fa fa-envelope-o fa-5x"></i>
                        </div>
                        <div class="col-xs-8 text-right">
                            <span> New messages </span>
                            <h2 class="font-bold">150</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="widget style1 yellow-bg">
                    <div class="row">
                        <div class="col-xs-4">
                            <i class="fa fa-user fa-5x"></i>
                        </div>
                        <div class="col-xs-8 text-right">
                            <span> New users </span>
                            <h2 class="font-bold">380</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="inqbox float-e-margins">
            <div class="inqbox-title border-top-primary">
                <h5>Number of Orders </h5>
                <div class="inqbox-tools">
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-wrench"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="#">Config option 1</a>
                        </li>
                        <li><a href="#">Config option 2</a>
                        </li>
                    </ul>
                    <a class="close-link">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="inqbox-content">
                <div id="morris-bar-chart"></div>
            </div>
        </div>
        <div class="inqbox float-e-margins">
            <div class="inqbox-title border-top-info">
                <h5>Transactions worldwide</h5>
                <div class="inqbox-tools">
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                    <a class="close-link">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="inqbox-content">
                <div class="row">
                    <div class="col-lg-6">
                        <table class="table table-hover margin bottom">
                            <thead>
                            <tr>
                                <th style="width: 1%" class="text-center">No.</th>
                                <th>Transaction</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td> Security doors
                                </td>
                                <td class="text-center small">16 Jun 2015</td>
                                <td class="text-center"><span class="label label-primary">$483.00</span></td>
                            </tr>
                            <tr>
                                <td class="text-center">2</td>
                                <td> Wardrobes
                                </td>
                                <td class="text-center small">10 Jun 2015</td>
                                <td class="text-center"><span class="label label-primary">$327.00</span></td>
                            </tr>
                            <tr>
                                <td class="text-center">3</td>
                                <td> Set of tools
                                </td>
                                <td class="text-center small">12 Jun 2015</td>
                                <td class="text-center"><span class="label label-warning">$125.00</span></td>
                            </tr>
                            <tr>
                                <td class="text-center">4</td>
                                <td> Panoramic pictures</td>
                                <td class="text-center small">22 Jun 2013</td>
                                <td class="text-center"><span class="label label-primary">$344.00</span></td>
                            </tr>
                            <tr>
                                <td class="text-center">5</td>
                                <td>Phones</td>
                                <td class="text-center small">24 Jun 2013</td>
                                <td class="text-center"><span class="label label-primary">$235.00</span></td>
                            </tr>
                            <tr>
                                <td class="text-center">6</td>
                                <td>Monitors</td>
                                <td class="text-center small">26 Jun 2013</td>
                                <td class="text-center"><span class="label label-primary">$100.00</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-lg-6">
                        <div id="world-map" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        Deals & Coupons
                    </div>
                    <div class="panel-body">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum tincidunt est vitae ultrices accumsan. Aliquam ornare lacus adipiscing, posuere lectus et, fringilla augue.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Latest Posts
                    </div>
                    <div class="panel-body">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum tincidunt est vitae ultrices accumsan. Aliquam ornare lacus adipiscing, posuere lectus et, fringilla augue.</p>
                        <p></p>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum tincidunt est vitae ultrices accumsan. Aliquam ornare lacus adipiscing, posuere lectus et, fringilla augue... <a href="">Read More</a></p>
                        <p></p>
                        <p></p>
                        <div class="singlepostinfo">Last updated on July 3rd, 2015 by <strong>Editorial Staff</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="inqbox float-e-margins">
            <div class="inqbox-title border-top-info">
                <h5>Notes</h5>
                <div class="inqbox-tools">
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                    <a class="close-link">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="inqbox-content paper" contenteditable="true">
                Send e-mails.<br>
                <s>meeting at 4 pm.</s><br>
                <s>Buy some coffee.</s><br>
                Talk with Mom about Kyle Doe.
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="inqbox-content">
            <div>
                <div>
                    <span>Memory</span>
                    <small class="pull-right">10/200 GB</small>
                </div>
                <div class="progress progress-small">
                    <div style="width: 60%;" class="progress-bar"></div>
                </div>
                <div>
                    <span>Bandwidth</span>
                    <small class="pull-right">20 GB</small>
                </div>
                <div class="progress progress-small">
                    <div style="width: 50%;" class="progress-bar"></div>
                </div>
                <div>
                    <span>Activity</span>
                    <small class="pull-right">73%</small>
                </div>
                <div class="progress progress-small">
                    <div style="width: 40%;" class="progress-bar"></div>
                </div>
                <div>
                    <span>FTP</span>
                    <small class="pull-right">400 GB</small>
                </div>
                <div class="progress progress-small">
                    <div style="width: 20%;" class="progress-bar progress-bar-danger"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="inqbox float-e-margins">
            <div class="inqbox-title border-top-danger">
                <h5>Projects</h5>
                <div class="inqbox-tools">
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-wrench"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="#">All Tasks</a>
                        </li>
                        <li><a href="#">New Task</a>
                        </li>
                    </ul>
                    <a class="close-link">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="inqbox-content">
                <div class="row">
                    <div class="col-sm-9 m-b-xs">
                        <div data-toggle="buttons" class="btn-group">
                            <label class="btn btn-sm btn-white"> <input type="radio" id="option1" name="options"> Day </label>
                            <label class="btn btn-sm btn-white active"> <input type="radio" id="option2" name="options"> Week </label>
                            <label class="btn btn-sm btn-white"> <input type="radio" id="option3" name="options"> Month </label>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="input-group"><input type="text" placeholder="Search" class="input-sm form-control"> <span class="input-group-btn">
                                                        <button type="button" class="btn btn-sm btn-primary"> Go!</button> </span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive no-more-tables">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Project </th>
                            <th>Name </th>
                            <th>Completed </th>
                            <th>Task</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td data-title="#">1</td>
                            <td data-title="Project">Project <small>Contact forms</small></td>
                            <td data-title="Name">Alden Richards</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">20%</td>
                            <td data-title="Date">Feb 5, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">2</td>
                            <td data-title="Project">Project <small>Contact forms</small></td>
                            <td data-title="Name">Maine Mendoza</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">20%</td>
                            <td data-title="Date">Feb 5, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">3</td>
                            <td data-title="Project">Project <small>Email forms</small></td>
                            <td data-title="Name">Kyle Mendoza</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">20%</td>
                            <td data-title="Date">August 5, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">4</td>
                            <td data-title="Project">Project <small>AI</small></td>
                            <td data-title="Name">Maine Mendoza</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">20%</td>
                            <td data-title="Date">September 5, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">5</td>
                            <td data-title="Project">Project <small>UX</small></td>
                            <td data-title="Name">Shiela Mae Suarez</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">30%</td>
                            <td data-title="Date">July 5, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">6</td>
                            <td data-title="Project">Project <small>UI</small></td>
                            <td data-title="Name">Jason Tolentino</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">15%</td>
                            <td data-title="Date">May 6, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">7</td>
                            <td data-title="Project">Project <small>UI</small></td>
                            <td data-title="Name">Jason Tolentino</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">15%</td>
                            <td data-title="Date">May 6, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">8</td>
                            <td data-title="Project">Project <small>UI</small></td>
                            <td data-title="Name">Jason Tolentino</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">15%</td>
                            <td data-title="Date">May 6, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">9</td>
                            <td data-title="Project">Project <small>Contact forms</small></td>
                            <td data-title="Name">Maine Mendoza</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">20%</td>
                            <td data-title="Date">Feb 5, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        <tr>
                            <td data-title="#">10</td>
                            <td data-title="Project">Project <small>Contact forms</small></td>
                            <td data-title="Name">Paulo Ballesteros</td>
                            <td data-title="Completed"><span class="pie">0.52/1.561</span></td>
                            <td data-title="Task">20%</td>
                            <td data-title="Date">Feb 5, 2015</td>
                            <td data-title="Action"><a href="#"><i class="fa fa-check text-navy"></i></a></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
