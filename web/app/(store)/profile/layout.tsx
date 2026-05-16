import ProfileBodyClassLayout from "./profile-body-class";

export default function ProfileLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <ProfileBodyClassLayout>{children}</ProfileBodyClassLayout>;
}
