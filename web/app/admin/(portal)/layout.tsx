import { StaffBodyClass } from "@/components/admin/staff-body-class";
import { StaffTopbar } from "@/components/store/staff-topbar";
import { requireStaff } from "@/lib/staff-session";

export default async function AdminPortalLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  const staff = await requireStaff();

  return (
    <>
      <StaffBodyClass />
      <StaffTopbar
        displayName={staff.displayName}
        role={staff.role}
        restaurantId={staff.restaurantId}
      />
      <main className="staff-main">{children}</main>
    </>
  );
}
