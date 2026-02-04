import type { SweetAlertOptions } from 'sweetalert2';
import Swal from 'sweetalert2';

const baseToastOptions: SweetAlertOptions = {
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    zIndex: 2000,
};

const baseConfirmOptions: SweetAlertOptions = {
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    zIndex: 2000,
};

export const useAlerts = () => {
    const toast = (options: SweetAlertOptions) =>
        Swal.fire({
            ...baseToastOptions,
            ...options,
        });

    const confirm = (options: SweetAlertOptions) =>
        Swal.fire({
            ...baseConfirmOptions,
            ...options,
        });

    return {
        toast,
        confirm,
    };
};
