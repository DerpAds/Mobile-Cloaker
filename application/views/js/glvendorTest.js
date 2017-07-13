	/* null,'WebKit','Microsoft','Mozilla', 'Google Inc.'*/

	function getGlVendor() {
	
		var gl = document.createElement('canvas').getContext('webgl');		
		var extension = gl.getExtension('WEBGL_debug_renderer_info');
		if (extension != undefined) {
			return gl.getParameter(extension.UNMASKED_VENDOR_WEBGL);
		}		
		return null;
	}

	function inBlockedGLVendors()
	{
		var vendor = getGlVendor();
		var result = getBlockedGLVendorList().indexOf(vendor) !== -1;
		if (result)
		{
			jslog('CHECK:GLVENDOR_BLOCKED: GlVendor: ' + vendor + ' in blocked list.');
		}
		else
		{
			jslog('CHECK:GLVENDOR_ALLOWED: GlVendor: ' + vendor + ' NOT in blocked list.');
		}
		return result;

	}