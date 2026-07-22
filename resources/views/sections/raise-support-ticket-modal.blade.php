<!-- Pencil Studio Modal -->
<div id="raiseSupportTicketModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pencil Studio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="text-xl font-weight-bold text-dark mb-2">{{ __('pencil_studio.header_title') }}</h2>
                        <p class="text-muted">{{ __('pencil_studio.header_subtitle') }}</p>
                    </div>

                    <!-- Support Options -->
                    <div class="row">
                        <!-- Web Development Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div>
                                            <h5 class="font-weight-bold text-dark mb-1">{{ __('pencil_studio.web_dev_title') }}</h5>
                                            <p class="text-muted small mb-0">{{ __('pencil_studio.web_dev_desc') }}</p>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-success mr-2"></i>
                                                <span class="text-muted small">SEO Friendly</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-success mr-2"></i>
                                                <span class="text-muted small">Responsive Design</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-success mr-2"></i>
                                                <span class="text-muted small">High Performance</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-success mr-2"></i>
                                                <span class="text-muted small">E-commerce Ready</span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="https://dipencil.com/" target="_blank"
                                       class="btn btn-secondary btn-sm">
                                        <i class="fa fa-external-link-alt mr-1"></i>
                                        {{ __('pencil_studio.visit_website') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Creative & Marketing Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-primary" style="background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%);">
                                <div class="card-body">
                                    <div class="position-relative">
                                        <span class="badge badge-primary position-absolute" style="top: 0; right: 0;">
                                            Art From Scratch
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center mb-3">
                                        <div>
                                            <h5 class="font-weight-bold text-dark mb-1">{{ __('pencil_studio.ui_ux_title') }}</h5>
                                            <p class="text-muted small mb-0">{{ __('pencil_studio.ui_ux_desc') }}</p>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">Brand Identity</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">UI/UX Design</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">Digital Marketing</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">Innovative Ideas</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">Social Media Management</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">Content Creation</span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="https://wa.me/201003778273" target="_blank"
                                       class="btn btn-primary btn-sm">
                                        <i class="fab fa-whatsapp mr-1"></i>
                                        {{ __('pencil_studio.schedule_consultation') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('app.close')</button>
            </div>
        </div>
    </div>
</div>
