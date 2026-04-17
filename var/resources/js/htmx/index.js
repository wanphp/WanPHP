import {registerConfirm} from './confirm';
import {registerRequestGuard} from './request-guard';
import {registerResponseHandler} from './response-handler';
import {registerLifecycle} from './lifecycle';

export function initHtmx() {
  registerConfirm();
  registerRequestGuard();
  registerResponseHandler();
  registerLifecycle();
}
